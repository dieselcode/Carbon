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

use \Carbon\Exception\SocketException as SocketException;

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
        $context = $errno = $errstr = null;

        if ($scheme == 'ssl') {
            $context = stream_context_create();

            if (file_exists($pem_file)) {
                stream_context_set_option($context, 'ssl', 'local_cert', $pem_file);
                stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
                stream_context_set_option($context, 'ssl', 'verify_peer', false);
                if (!is_null($pem_pass)) {
                    stream_context_set_option($context, 'ssl', 'passphrase', $pem_pass);
                }
            } else {
                throw new SocketException('Unable to locate local (pem) certificate for SSL server');
            }
        }

        $address = "{$scheme}://{$host}:{$port}";

        $this->master = stream_socket_server(
            $address,
            $errno,
            $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
        ); // TODO: Add context here when SSL is working

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

    public function readWholeBuffer($resource, $length = 2048)
    {
        $remaining = $length;

        $buffer = '';
        $metadata['unread_bytes'] = 0;

        do {
            if (feof($resource)) {
                return $buffer;
            }

            $result = fread($resource, $length);

            if ($result === false) {
                return $buffer;
            }

            $buffer .= $result;

            if (feof($resource)) {
                return $buffer;
            }

            $continue = false;

            if ($this->first_read == true && strlen($result) == 1) {
                // Workaround Chrome behavior (still needed?)
                $continue = true;
            }
            $this->first_read = false;

            if (strlen($result) == $length) {
                $continue = true;
            }

            // Continue if more data to be read
            $metadata = stream_get_meta_data($resource);
            if ($metadata && isset($metadata['unread_bytes']) && $metadata['unread_bytes']) {
                $continue = true;
                $length = $metadata['unread_bytes'];
            }
        } while ($continue);

        return $buffer;
    }

}

?>
