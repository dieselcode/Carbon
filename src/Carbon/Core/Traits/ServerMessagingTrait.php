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

namespace Carbon\Core\Traits;

trait ServerMessagingTrait
{
    /**
     * Sends data from the current connection to all other connections on the _server_
     *
     * @param string $message
     */
    public function sendAll($message)
    {
        foreach ($this->connections as $connection) {
            $connection->send($message);
        }
    }

    /**
     * Sends data from the current connection to all other connections on the _server_,
     * excluding the sending connection.
     *
     * Only valid for sending HyBi::TextFrame type packets.
     *
     * @param Connection $excludeConnection
     * @param string     $message
     */
    public function broadcastAll($message, \Carbon\Core\Connection $exclude_client)
    {
        $client = array_search($exclude_client, $this->connections);
        foreach ($this->connections as $k => $v) {
            if ($k == $client) {
                continue;
            }
            $v->send($message);
        }
    }

    // send to entire *route*
    public function send($message)
    {
        $this->log(
            'Sending a message to all users on "' .
            $this->current_route . '" (' . count($this->groups[$this->current_route]['connections']) .' users)');

        foreach ($this->groups[$this->current_route]['connections'] as $connection) {
            $connection->send($message);
        }
    }

    // broacast to *route*
    public function broadcast($message, \Carbon\Core\Connection $exclude_client)
    {
        $this->log(
            'Broadcasting a message to all users on "' .
            $this->current_route . '" (' . count($this->groups[$this->current_route]['connections']) .' users)');

        $client = array_search($exclude_client, $this->groups[$exclude_client->path]['connections']);
        foreach ($this->groups[$exclude_client->path]['connections'] as $k => $v) {
            if ($k == $client) {
                continue;
            }
            $v->send($message);
        }
    }
}

?>