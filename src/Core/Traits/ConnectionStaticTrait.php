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

trait ConnectionStaticTrait
{

    public static function getOrigin($headers) {
        $origin = (isset($headers['Origin'])) ? $headers['Origin'] : null;
        if (is_null($origin) && isset($headers['Sec-WebSocket-Origin'])) {
            $origin = (isset($headers['Sec-WebSocket-Origin'])) ? $headers['Sec-WebSocket-Origin'] : null;
        }

        return $origin;
    }

    public static function parseHeaders($response)
    {

        $parts = explode("\r\n\r\n", $response, 2);

        if (count($parts) != 2) {
            $parts = array($parts, '');
        }

        list($headers, ) = $parts;

        $return = array();
        foreach (explode("\r\n", $headers) as $header) {
            $parts = explode(': ', $header, 2);
            if (count($parts) == 2) {
                list($name, $value) = $parts;
                if (!isset($return[$name])) {
                    $return[$name] = $value;
                } else {
                    if (is_array($return[$name])) {
                        $return[$name][] = $value;
                    } else {
                        $return[$name] = array($return[$name], $value);
                    }
                }
            }
        }

        if (preg_match("/GET (.*) HTTP/", $response, $match)) {
            $return['GET'] = $match[1];
        }

        return $return;
    }


    public static function isJSON($string)
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }

    public static function getIP($socket)
    {
        return stream_socket_get_name($socket, true);
    }

    public static function buildHeaders($header_array)
    {
        $header_str = '';
        foreach ($header_array as $key => $value) {
            $header_str .= $key . ': ' . $value . "\r\n";
        }

        return $header_str;
    }

    public static function randomHash($length = 10)
    {
        $hex = null;

        if (function_exists('openssl_random_pseudo_bytes')) {
            for ($i = -1; $i <= ($length * 4); $i++) {
                $bytes = openssl_random_pseudo_bytes($i);
                $hex   = bin2hex($bytes);
                if (strlen($hex) == $length) {
                    break;
                }
            }
        } else {
            $hex = hash('sha256', time() . uniqid(__FUNCTION__, true));
        }

        return substr($hex, 0, $length);
    }

}

?>