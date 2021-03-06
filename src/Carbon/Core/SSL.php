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

use Carbon\Core\Settings;
use Carbon\Exception\SSLException;

class SSL
{
    static $settings;
    static $enabled = false;

    private static function _load()
    {
        if (empty(self::$settings)) {
            self::$enabled = true;
            self::$settings = Settings::get('ssl');
        }
    }

    public static function enabled()
    {
        return !!self::$enabled;
    }

    public static function getContext()
    {
        self::_load();
        $context = stream_context_create();

        if (self::hasCert()) {
            if (!self::isDateValid()) {
                throw new SSLException('SSL certificate is expired.  Please supply a valid SSL cert.');
            }
        } else {
            throw new SSLException('Invalid SSL certificate supplied.');
        }

        stream_context_set_option($context, 'ssl', 'local_cert', self::$settings['local_cert_path']);
        if (!empty(self::$settings['local_cert_passphrase'])) {
            stream_context_set_option($context, 'ssl', 'passphrase', self::$settings['local_cert_passphrase']);
        }
        stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
        stream_context_set_option($context, 'ssl', 'verify_peer', false);

        return $context;
    }

    protected static function isDateValid()
    {
        self::_load();

        $check = openssl_x509_parse(file_get_contents(self::$settings['local_cert_path']));

        if ($check['validTo_time_t'] < time()) {
            return false;
        }

        return true;
    }

    protected static function hasCert()
    {
        self::_load();
        return !!file_exists(self::$settings['local_cert_path']);
    }

}

?>