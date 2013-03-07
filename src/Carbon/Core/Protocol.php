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
    \Carbon\Core\Protocol\Frame,
    \Carbon\Exception\ProtocolException;

class Protocol extends Frame
{
    // minimum HyBi version to accept
    const ALLOW_MIN_VERSION = 8;

    const ContinuationFrame = 0x00;
    const TextFrame         = 0x01;
    const BinaryFrame       = 0x02;
    const CloseFrame        = 0x08;
    const PingFrame         = 0x09;
    const PongFrame         = 0x10;

    const CloseNormal       = 1000;
    const CloseGoingAway    = 1001;
    const CloseProtocolErr  = 1002;
    const CloseBadData      = 1003;
    const CloseWrongData    = 1007;
    const ClosePolicy       = 1008;
    const CloseMsgTooBig    = 1009;
    const CloseExtensions   = 1010;
    const CloseUnexpected   = 1011;

    public function __construct(Connection $connection, $data = null, $process = false)
    {
        if ($process) {
            $this->process($connection, $data);
        }
    }

    public function process(Connection $connection, $decoded)
    {
        $this->setPayload($decoded['payload']);
        $this->setType($decoded['opcode']);

        if (self::isControlFrame($this->getType())) {
            switch ($this->getType()) {

                case self::CloseFrame:
                    $connection->log('Got a close frame....');

                    $frame = self::encode(self::CloseNormal, self::CloseFrame);
                    $connection->send($frame);
                    $connection->onDisconnect();

                    break;

                case self::PongFrame:
                    // no comfirmation needs to be sent back to user
                    $connection->updateActivity();

                    if ($connection->route && $connection->server->hasCallback($connection->path, 'pong')) {
                        $connection->route['pong']($connection);
                    }

                    break;

                case self::PingFrame:
                    // send the pong, the activate a pong handler
                    $frame = self::encode('PONG', self::PongFrame);
                    $connection->send($frame);
                    $connection->updateActivity();

                    if ($connection->route && $connection->server->hasCallback($connection->path, 'ping')) {
                        $connection->route['ping']($connection);
                    }

                    break;
            }
        }

        return $this;
    }


    public static function frame($message, $connection, $messageType = self::TextFrame, $messageContinues = false)
    {
        switch ($messageType) {
            case self::ContinuationFrame:
                $b1 = 0;
                break;
            case self::TextFrame:
                $b1 = ($connection->sendingContinuous) ? 0 : 1;
                break;
            case self::BinaryFrame:
                $b1 = ($connection->sendingContinuous) ? 0 : 2;
                break;
            case self::CloseFrame:
                $b1 = 8;
                break;
            case self::PingFrame:
                $b1 = 9;
                break;
            case self::PongFrame:
                $b1 = 10;
                break;
        }
        if ($messageContinues) {
            $connection->sendingContinuous = true;
        } else {
            $b1 += 128;
            $connection->sendingContinuous = false;
        }

        $length      = strlen($message);
        $lengthField = '';

        if ($length < 126) {
            $b2 = $length;
        } elseif ($length <= 65536) {
            $b2        = 126;
            $hexLength = dechex($length);

            if (strlen($hexLength) % 2 == 1) {
                $hexLength = '0' . $hexLength;
            }

            $n = strlen($hexLength) - 2;

            for ($i = $n; $i >= 0; $i = $i - 2) {
                $lengthField = chr(hexdec(substr($hexLength, $i, 2))) . $lengthField;
            }

            while (strlen($lengthField) < 2) {
                $lengthField = chr(0) . $lengthField;
            }
        } else {
            $b2        = 127;
            $hexLength = dechex($length);

            if (strlen($hexLength) % 2 == 1) {
                $hexLength = '0' . $hexLength;
            }

            $n = strlen($hexLength) - 2;

            for ($i = $n; $i >= 0; $i = $i - 2) {
                $lengthField = chr(hexdec(substr($hexLength, $i, 2))) . $lengthField;
            }

            while (strlen($lengthField) < 8) {
                $lengthField = chr(0) . $lengthField;
            }
        }

        return chr($b1) . chr($b2) . $lengthField . $message;
    }

    public static function isControlFrame($frame)
    {
        $controls = array(self::CloseFrame, self::PingFrame, self::PongFrame);

        return array_search($frame, $controls) !== false;
    }

    public function deframe($message, $connection)
    {
        $headers   = $this->extractHeaders($message);
        $pongReply = false;
        $willClose = false;

        switch ($headers['opcode']) {
            case self::ContinuationFrame:
            case self::TextFrame:
            case self::BinaryFrame:
                break;
            case self::CloseFrame:
                // todo: close the connection
                $connection->hasSentClose = true;
                return;
            case self::PingFrame:
                $pongReply = true;
            case self::PongFrame:
                break;
            default:
                //$this->disconnect($user); // todo: fail connection
                $willClose = true;
                break;
        }

        if ($connection->handlingPartialPacket) {
            $message = $connection->partialBuffer . $message;
            $connection->handlingPartialPacket = false;

            return $this->deframe($message, $connection);
        }

        if ($this->checkRSVBits($headers, $connection)) {
            return false;
        }

        if ($willClose) {
            // todo: fail the connection
            return false;
        }

        $payload = $connection->partialMessage . $this->extractPayload($message, $headers);

        if ($pongReply) {
            $reply = $this->frame($payload, $connection, 'pong');
            stream_socket_sendto($connection->socket, $reply);

            return false;
        }
        if (extension_loaded('mbstring')) {
            if ($headers['length'] > mb_strlen($payload)) {
                $connection->handlingPartialPacket = true;
                $connection->partialBuffer         = $message;

                return false;
            }
        } else {
            if ($headers['length'] > strlen($payload)) {
                $connection->handlingPartialPacket = true;
                $connection->partialBuffer         = $message;

                return false;
            }
        }

        $payload = $this->applyMask($headers, $payload);

        if ($headers['fin']) {
            $connection->partialMessage = '';
            $headers['payload'] = $payload;

            return $headers;
        }

        $connection->partialMessage = $payload;

        return false;
    }

    protected function extractHeaders($message)
    {
        $header           = array('fin' => $message[0] & chr(128), 'rsv1' => $message[0] & chr(64), 'rsv2' => $message[0] & chr(32), 'rsv3' => $message[0] & chr(16), 'opcode' => ord($message[0]) & 15, 'hasmask' => $message[1] & chr(128), 'length' => 0, 'mask' => "");
        $header['length'] = (ord($message[1]) >= 128) ? ord($message[1]) - 128 : ord($message[1]);

        if ($header['length'] == 126) {
            if ($header['hasmask']) {
                $header['mask'] = $message[4] . $message[5] . $message[6] . $message[7];
            }
            $header['length'] = ord($message[2]) * 256 + ord($message[3]);
        } elseif ($header['length'] == 127) {
            if ($header['hasmask']) {
                $header['mask'] = $message[10] . $message[11] . $message[12] . $message[13];
            }
            $header['length'] = ord($message[2]) * 65536 * 65536 * 65536 * 256 + ord($message[3]) * 65536 * 65536 * 65536 + ord($message[4]) * 65536 * 65536 * 256 + ord($message[5]) * 65536 * 65536 + ord($message[6]) * 65536 * 256 + ord($message[7]) * 65536 + ord($message[8]) * 256 + ord($message[9]);
        } elseif ($header['hasmask']) {
            $header['mask'] = $message[2] . $message[3] . $message[4] . $message[5];
        }

        return $header;
    }

    protected function extractPayload($message, $headers)
    {
        $offset = 2;
        if ($headers['hasmask']) {
            $offset += 4;
        }
        if ($headers['length'] > 65535) {
            $offset += 8;
        } elseif ($headers['length'] > 125) {
            $offset += 2;
        }

        return substr($message, $offset);
    }

    protected function applyMask($headers, $payload)
    {
        $effectiveMask = "";
        if ($headers['hasmask']) {
            $mask = $headers['mask'];
        } else {
            return $payload;
        }

        while (strlen($effectiveMask) < strlen($payload)) {
            $effectiveMask .= $mask;
        }
        while (strlen($effectiveMask) > strlen($payload)) {
            $effectiveMask = substr($effectiveMask, 0, -1);
        }

        return $effectiveMask ^ $payload;
    }

    protected function checkRSVBits($headers, $user)
    { // override this method if you are using an extension where the RSV bits are used.
        if (ord($headers['rsv1']) + ord($headers['rsv2']) + ord($headers['rsv3']) > 0) {
            //$this->disconnect($user); // todo: fail connection
            return true;
        }

        return false;
    }

    public static function handshake(Connection $connection, $data)
    {
        $protocol = null;
        $headers  = array();

        // do something a bit nicer here
        $lines = preg_split("/\r\n/", $data);
        if (count($lines) && preg_match('/<policy-file-request.*>/', $lines[0])) {
            $connection->log('Flash policy file request');
            $connection->serverFlashPolicy();

            return false;
        }
        unset($lines);

        $headers = $connection->parseHeaders($data);
        $path    = $headers['GET'];

        // TODO: Do origin checks here
        if (Settings::get('origins')['origin_check_enabled']) {
            $allowed_origins = Settings::get('origins')['allowed_origins'];

            if (!empty($allowed_origins)) {
                $origin = Connection::getOrigin($headers);
                $host = parse_url($origin, PHP_URL_HOST) ?: $origin;

                if (in_array($host, $allowed_origins)) {
                    $connection->log('Origin accepted and valid (' . $origin . ')...');
                } else {
                    $connection->log('Origin was denied (' . $origin . ')...');
                    return false;
                }

            }

        }

        $connection->log('Performing handshake');

        // get the appropriate application
        $connection->path = ltrim($path, '/');

        // if the user defined a "/" route, it gets renamed to "__main__"
        if (empty($connection->path)) {
            $connection->path = '__main__';
        }

        // check the route and retrieve it
        if ($connection->server->hasRoute($connection->path)) {
            $connection->route = $connection->server->getRoute($connection->path);
        } else {
            $connection->log('Invalid route accessed: "' . $connection->path . '"');
            $connection->onDisconnect();

            return false;
        }

        // do the secret handshake  ;)
        if (version_compare($headers['Sec-WebSocket-Version'], self::ALLOW_MIN_VERSION, '>=')) {
            if (array_key_exists('Sec-WebSocket-Key', $headers)) {
                $connection->protocol = 'HyBi';

                $out_header = array('Sec-WebSocket-Accept' => base64_encode(sha1($headers['Sec-WebSocket-Key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true)));

                // this is a small fix for Webkit.. if we send this back and it's empty by the client, the client will fail.
                if (array_key_exists('Sec-WebSocket-Protocol', $headers)) {
                    $out_header['Sec-WebSocket-Protocol'] = ltrim($connection->path, '/');
                }
            } else {
                $connection->log('WebSocket connection key was invalid or not found.  Client error.');
                $connection->onDisconnect();

                return false;
            }
        } else {
            $connection->log('Incorrect headers or old protocol.  Must use a HyBi compatible client (version 8 or higher)');
            $connection->onDisconnect();

            return false;
        }

        $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
                   "Upgrade: " . $headers['Upgrade'] . "\r\n" .
                   "Connection: " . $headers['Connection'] . "\r\n" .
                   $connection->buildHeaders($out_header) . "\r\n";

        $connection->server->writeWholeBuffer($connection->socket, $upgrade);

        $connection->handshaked = true;
        $connection->log('Handshake sent, protocol: ' . $connection->protocol);

        //add the user to a routing group
        $connection->server->addRoutingGroup($connection->path);
        $connection->server->addRouteConnection($connection->path, $connection->socket, $connection);

        if ($connection->route && $connection->server->hasCallback($connection->path, 'connect')) {
            $connection->route['connect']($connection, $headers);
        }

        // free some memory
        unset($out_header, $headers, $path);

        return true;
    }

}

?>