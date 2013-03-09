<?php

/**
 * Carbon WebSocket Server
 *
 * Copyright (c) 2013, Andrew Heebner, All rights reserved.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.
 */

namespace Carbon\Core;

use \Carbon\Core\Connection,
    \Carbon\Core\Socket,
    \Carbon\Core\Protocol,
    \Carbon\Core\Settings,
    \Carbon\Tools\CLI,
    \Carbon\Exception\ServerException,
    \Carbon\Exception\SocketException;

/**
 * TODO: Implement throttling (connections per IP, max connections, requests per minute)
 * TODO: Go through all files in project and put better error handling in place, as well as better logging
 * TODO: Move all debug and verbose output to Tools\Logger.php
 */

class Server extends Socket
{
    use \Carbon\Core\Traits\ServerRoutingTrait;
    use \Carbon\Core\Traits\ServerDebugTrait;
    use \Carbon\Core\Traits\ServerMessagingTrait;


    private $connections        = array();
    private $debug              = false;
    private $routes             = array();
    public  $groups             = array();
    private $current_route      = null;
    private $current_action     = null;

    public function __construct($config_file = null, \Closure $callback = null)
    {
        ob_implicit_flush(true);
        set_time_limit(0);

        if (!is_null($config_file)) {
            if (false === Settings::load($config_file)) {
                throw new ServerException('Unable to locate and load configuration file "' . $config_file . '"');
            }
        }

        // set up a default timezone
        date_default_timezone_set(Settings::get('options')['default_timezone']);

        // route the second config check to the CLI.  See if we have any args
        // and if we do, override our 'written' config with the CLI params.
        CLI::parse();

        try {
            parent::__construct(
                Settings::get('server')['host'],
                Settings::get('server')['port'],
                Settings::get('server')['scheme']
            );

            $this->log('Created socket server...');

            if (!is_null($callback) && $callback instanceof \Closure) {
                $this->log('Executing server start callback...');
                $callback();
            }
        } catch (SocketException $e) {
            throw new ServerException($e->getMessage());
        }

        // setup our dynamic
        $this->log("Server created: [" . $this->getServerString() . "]");
    }

    public function run()
    {
        $this->log('Running main loop...');

        while (true) {

            clearstatcache();
            if (function_exists('gc_collect_cycles')) {
                if (gc_enabled()) {
                    gc_collect_cycles();
                }
            }

            $changed = $this->allsockets;
            $write   = null;
            $except  = null;

            if (@stream_select(
                $changed,
                $write,
                $except,
                Settings::get('options')['socket_select_timeout_sec'],
                Settings::get('options')['socket_select_timeout_usec']) === false
            ) {
                $this->log('Stream select failed...');
                break;
            }

            foreach ($changed as $resource) {

                if ($resource == $this->master) {
                    $socket = stream_socket_accept($this->master);

                    if ($socket < 0) {
                        $this->log('Socket error: ' . socket_strerror(socket_last_error($resource)));
                        continue;
                    } else {
                        $connection = new Connection($this, $socket);
                        $this->log('Accepted new connection from ' . $connection->getId() . '...');
                        @$this->connections[$socket] = $connection;
                        $this->allsockets[] = $socket;
                    }
                } else {
                    //$buffer = stream_socket_recvfrom($resource, Settings::get('server')['max_buffer']);
                    $buffer = $this->readWholeBuffer($resource, Settings::get('server')['max_buffer']);

                    @$connection = $this->connections[$resource];
                    $protocol = new Protocol($connection);

                    if (strlen($buffer) == 0) {
                        $connection->onDisconnect();
                    } else {
                        if (!$connection->handshaked) {
                            if (!$protocol->handshake($connection, $buffer)) {
                                $connection->onDisconnect();
                            }
                        } else {
                            if ($message = $protocol->deframe($buffer, $connection)) {
                                $connection->delegate($protocol, $message);
                                if ($connection->hasSentClose) {
                                    $connection->onDisconnect();
                                }
                            } else {
                                do {
                                    $bytes = stream_socket_recvfrom(
                                        $resource,
                                        Settings::get('server')['max_buffer'],
                                        STREAM_PEEK
                                    );

                                    if ($bytes > 0) {
                                        $buffer = @stream_socket_recvfrom(
                                            $resource,
                                            Settings::get('server')['max_buffer']
                                        );

                                        if ($message = $protocol->deframe($buffer, $connection)) {
                                            $connection->delegate($protocol, $message);
                                            if ($connection->hasSentClose) {
                                                $connection->onDisconnect();
                                            }
                                        }
                                    }
                                } while($bytes > 0);
                            }
                        }
                    }

                }

            }
        }
    }

    /**
     * Delete connection from server
     *
     * @param Connection $connect
     */
    public function socketDisconnect($connection)
    {
        $socket_key  = array_search($connection, $this->connections);
        $s_key       = array_search($socket_key, $this->allsockets);

        if ($this->hasRoute($connection->path)) {
            $rsocket_key = array_search($connection, $this->groups[$connection->path]['connections']);
        }

        if ($socket_key && $s_key) {
            unset($this->connections[$socket_key]);
            unset($this->allsockets[$s_key]);

            if (!empty($rsocket_key) && $this->hasRoute($connection->path)) {
                unset($this->groups[$connection->path]['connections'][$rsocket_key]);
            }
        }
    }

    public function writeHeader($socket, $code, $body)
    {
        $buffer = 'HTTP/1.1 ' . $code . ' ' . $body;
        $this->writeWholeBuffer($socket, $buffer);
    }

    public function getServerString() {
        return Settings::get('server')['scheme'] . "://" .
               Settings::get('server')['host'] . ":" .
               Settings::get('server')['port'];
    }


}

?>