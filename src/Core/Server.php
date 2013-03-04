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
    \Carbon\Core\Helpers\DataTrigger,
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
    use \Carbon\Core\Traits\ServerTriggerTrait;


    private $connections        = array();
    private $debug              = false;
    private $routes             = array();
    public  $groups             = array();
    private $current_route      = null;
    private $current_action     = null;


    /*
     * TODO: prepopulate config variable with values.  This will help with an Express Server integration
     */

    public $config          = array(
        'server' => array(
            'scheme' => 'tcp',
            'host' => '0.0.0.0',
            'port' => 12345,
            'max_buffer' => 2048
        ),
        'options' => array(
            'default_timezone' => 'America/New_York'
        ),
        'ssl' => array(),
        'origin_whitelist' => array()
    );


    public function __construct($config_file = null)
    {
        date_default_timezone_set($this->config['options']['default_timezone']);
        ob_implicit_flush(true);
        set_time_limit(0);

        if (!is_null($config_file)) {
            if (false === $this->loadConfig($config_file)) {
                throw new ServerException('Unable to locate and load configuration file "' . $config_file . '"');
            }
        }

        try {
            parent::__construct(
                $this->config['server']['host'],
                $this->config['server']['port'],
                $this->config['server']['scheme']
            );

            $this->log('Created socket server...');
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
                $this->config['options']['socket_select_timeout_sec'],
                $this->config['options']['socket_select_timeout_usec']) === false
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
                    $buffer = stream_socket_recvfrom($resource, $this->config['server']['max_buffer']);
                    @$connection = $this->connections[$resource];
                    $protocol = new Protocol($connection);

                    if (strlen($buffer) == 0) {
                        $connection->onDisconnect();
                    } else {
                        if (!$connection->handshaked) {
                            $protocol->handshake($connection, $buffer);
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
                                        $this->config['server']['max_buffer'],
                                        STREAM_PEEK
                                    );

                                    if ($bytes > 0) {
                                        $buffer = @stream_socket_recvfrom(
                                            $resource,
                                            $this->config['server']['max_buffer']
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

    public function loadConfig($config_file)
    {
        if (file_exists($config_file)) {
            if (false !== ($ini = parse_ini_file($config_file, true))) {
                foreach ($ini as $k => $v) { // section
                    $this->config[$k] = $v;
                }

                return true;
            }

        }

        return false;
    }

    public function getServerString() {
        return $this->config['server']['scheme'] . "://" .
               $this->config['server']['host'] . ":" .
               $this->config['server']['port'];
    }


}

?>