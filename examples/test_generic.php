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

require_once '../vendor/autoload.php';

use \Carbon\Core\Helpers\DataTrigger;

try {
    // init the server with our own ini file
    $server = new \Carbon\Core\Server('settings.generic.php');
    $server->setDebug(true);


    // default route can be set as "/" (that will take a zero index path)
    $server->route('/echo', function ($route) use ($server) {

        /*
         * Data triggers can be coded as the following
         *
         * $trigger = new DataTrigger(function(...) {
         *     // do something with the data here
         * });
         *
         * $route->addDataTrigger($trigger, 'special_message');
         *
         *
         * If the server recognizes a JSON data packet, and the message 'type'
         * is equal to 'special_message', then we'll trigger the callback that
         * the user has described... simple.  Easy way to fragment some functionality.
         *
         * ALSO... triggers are route-based only.  There will be no support for server-wide
         * routing triggers.
         *
         * If a trigger fails to match the key => value setup that the user defined,
         * the data will fail the trigger and instead dump the data to the user's defined
         * generic 'data' handler.
         *
         * Trigger handlers defer the data from the main route's 'data' handler.  There are
         * no plans to change this behavior.  There's no sense in duplicating data in two
         * different places.  This is why the failed trigger fallback was put in place.
         */

        $trigger = new DataTrigger(function($raw_data, $decoded_data, $connection) use ($route, $server) {
            $msg = $decoded_data->message;
            $decoded_data->message = 'You sent "'.$msg.'"! (special_message handler)';
            $connection->send(json_encode($decoded_data));
        });

        // This will only fire if the key "special_message" has a value of "test_value"
        // set the expected value argument to null to only check for the key (or just omit the argument)
        //$route->addDataTrigger($trigger, 'special_message', 'test_value');

        // to catch any value on the "special_message" key...
        $route->addDataTrigger($trigger, 'special_message');



        $route->on('data', function ($raw_data, $decoded_data, $connection) use ($route, $server) {

            // $decoded_data = If the data is determined as JSON, it is decoded here (will be null if none)
            // $raw_data = whether decoded or not, this is the raw input as passed from the Protocol::decode() method
            if (!empty($decoded_data)) {
                $message = $decoded_data->message;
                $decoded_data->message = 'You sent "'.$message.'"! (ECHO1)';
                $connection->send(json_encode($decoded_data));
            } else {
                $route->broadcast($raw_data, $connection);
            }

            /**
             * $connection->send() sends back to issuing socket (1-to-1 exchange)
             *
             * $route->send("message") sends to all server route sockets including the issuing socket (1-to-All)
             * $route->broadcast("message", $connection) is the same as above, except it excludes the issuing socket (1-to-Most)
             *
             * $server->sendAll('message') sends to all *server* sockets
             * $server->broadcastAll('message', $connection) sends to all server sockets except the issuing socket
             */
        });

        $route->on('data.binary', function($binary_data, $connection) use ($route, $server) {
            // do something with the binary data
        });

        $route->on('connect', function ($connection, $headers) use ($route) {
            // $headers contains the connection headers (may be useful for determining some stuff)
            $connection->setAlias('WebSocketUser_' . $connection->getId());
            echo 'Client Connected (' . $connection->getAlias() . ')' . PHP_EOL;
        });

        $route->on('disconnect', function ($connection) use ($route) {
            echo 'Client quit (' . $connection->getId() . ')' . PHP_EOL;
        });

        /**
         * TODO: The following is hypothetical.  It does not actually work.
         *
         * We need to setup a loop check in Server::run() to check against connected sockets
         * and purge them if need be.  Allow the administrator to set up a time limit to which the
         * connected client has to abide by.  This can be whether they send a message, send an
         * actual ping, or whatever... decide the specifics soon.
         */
        $route->on('ping', function($connection) use ($route) {     // connection pinged the server
            /*
             * $connection->getActivity() contains a timestamp of the user's latest activity instance
             */
        });

        $route->on('pong', function($connection) use ($route) {     // server pinged connection
            /*
             * $connection->getActivity() contains a timestamp of the user's latest activity instance
             */
        });

    });

    /*
     * Initialize and run the server
     */
    $server->run();

} catch (\Carbon\Exception\ServerException $e) {
    var_dump($e->getMessage());
}

?>