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

use \Carbon\Exception\ProtocolException,
    \Carbon\Core\AbstractConnection,
    \Carbon\Core\Protocol,
    \Carbon\Tools\DataContainer;


class Connection extends AbstractConnection
{
    use \Carbon\Core\Traits\ConnectionStaticTrait;

    public $server          = null;
    public $socket          = null;
    public $route           = null;
    public $path            = null;
    public $peer_name       = null;
    public $handshaked      = false;
    public $protocol        = null;
    public $last_activity   = null;
    public $connection_time = null;
    public $alias           = '';

    public $handlingPartialPacket   = false;
    public $partialBuffer           = '';
    public $sendingContinuous       = false;
    public $partialMessage          = '';

    public $hasSentClose    = false;


    public function __construct($server, $socket)
    {
        $this->server           = $server;
        $this->socket           = $socket;
        $this->peer_name        = self::getIP($socket);
        $this->connection_time  = time();
        $this->id               = self::randomHash();
        $this->alias            = $this->id;

        $this->log(sprintf('Connected... (Assigned ID: %s)', $this->id));
    }

    // send the proper data to the appropriate handler
    public function delegate(Protocol $protocol, $data)
    {
        try {
            // truly process data here
            $decoded = $protocol->process($this, $data, true);

            // don't delegate control frames
            if ($protocol->isControlFrame($decoded->getType())) {
                return;
            }

            switch ($decoded->getType()) {
                case Protocol::TextFrame:
                    $this->log('Incoming text frame; Delegating to onData');
                    $this->onData($decoded);
                    break;

                case Protocol::BinaryFrame:
                    $this->log('Incoming binary data frame; Delegating to onBinaryData');
                    $this->onBinaryData($decoded);
                    break;
            }

        } catch (ProtocolException $e) {
            $this->log(sprintf('Decoding data frame failed.  Error: "%s"', $e->getMessage()));
            $this->onDisconnect();
        }
    }

    public function onBinaryData($data)
    {
        if ($this->route && $this->server->hasCallback($this->path, 'data.binary')) {
            $this->route['data.binary']($data->getPayload(), $this);
        }

        $this->updateActivity();
    }

    /**
     * Receive client data.
     *
     * @param string $data
     */
    public function onData($data)
    {
        $container = new DataContainer;

        // route to the server-specified 'data' action
        $raw_data = $data->getPayload();
        $dec_data = (self::isJSON($raw_data)) ? json_decode($raw_data) : null;

        // contain our data
        $container->setData('raw', $raw_data);
        $container->setData('decoded', $dec_data);

        if ($this->route && $this->server->hasCallback($this->path, 'data')) {
            $this->route['data']($container, $this);
        }

        $this->updateActivity();
    }

    public function onDisconnect($reason = '')
    {
        $this->log(sprintf('Disconnected (%s)', $this->id));

        if ($this->route && $this->server->hasCallback($this->path, 'disconnect')) {
            $this->route['disconnect']($this);
        }

        /**
         * TODO: Allow valid close reasons here...
         */
        $this->send('1000', Protocol::CloseFrame);
        $this->server->socketDisconnect($this);

        @stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
    }

    /**
     * Send to current socket
     *
     * @param string $data
     */
    public function send($data, $type = Protocol::TextFrame)
    {
        $encoded = Protocol::frame($data, $this, $type);
        $this->server->writeWholeBuffer($this->socket, $encoded);
    }

    /*
     * TODO: Move this to Tools\Logger.php
     */

    /**
     * Console log. Work in debug mode
     *
     * @param string $message
     * @param string $type = 'info'
     */
    public function log($message, $type = 'info')
    {
        if ($this->server->getDebug()) {
            $this->server->log('[client ' . self::getIP($this->socket) . '] ' . $message, $type);
        }
    }


}

?>