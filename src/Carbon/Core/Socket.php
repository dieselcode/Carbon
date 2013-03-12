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

use Carbon\Exception\SSLException;
use \Carbon\Exception\SocketException,
    \Carbon\Core\SSL;

class Socket
{

    protected $master;
    protected $allsockets = array();
    protected $first_read = true;


    public function __construct($host = '0.0.0.0', $port = 12345, $scheme = 'tcp', $pem_file = null, $pem_pass = null)
    {
        $this->createServerSocket($host, $port, $scheme, $pem_file, $pem_pass);
    }

    protected function createServerSocket($host, $port, $scheme = 'tcp', $pem_file = null, $pem_pass = null)
    {
        $errno = $errstr = null;
        $context = stream_context_create();

        // use TLS for the server scheme if we want to be secured.
        $scheme = (in_array($scheme, array('tls', 'ssl', 'sslv2', 'sslv3'))) ? 'tls' : 'tcp';

        if ($scheme == 'tls') {
            try {
                $context = SSL::getContext();
            } catch (SSLException $e) {
                throw new SocketException('Socket failed to initialize via SSL: ' . $e->getMessage());
            }
        }

        $address = "{$scheme}://{$host}:{$port}";

        $this->master = stream_socket_server(
            $address,
            $errno,
            $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            $context
        );

        if (!is_resource($this->master)) {
            throw new SocketException('stream_socket_server() failed, reason: ' . $errstr);
        }

        $this->allsockets[] = $this->master;
    }

    public function writeWholeBuffer($fp, $string)
    {
        for ($written = 0; $written < strlen($string); $written += $fwrite) {
            $fwrite = @fwrite($fp, substr($string, $written));
            if ($fwrite === false) {
                return $written;
            }
        }

        return $written;
    }

    public static function readWholeBuffer($resource, $length = 8192)
    {
        $buffer   = '';
        $buffsize = $length;

        do {
            if (feof($resource)) {
                return false;
            }

            $result = fread($resource, $buffsize);

            if ($result == false || feof($resource)) {
                return false;
            }

            if (SSL::enabled() && strlen($result) == 1) {
                $result .= fread($resource, $buffsize);
            }

            $buffer  .= $result;
            $metadata = stream_get_meta_data($resource);
            $unread   = $metadata['unread_bytes'];
            $buffsize = ($unread > $buffsize) ? $buffsize : $metadata['unread_bytes'];
        } while ($unread > 0);

        return $buffer;

    }

}

?>
