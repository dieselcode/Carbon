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

namespace Carbon\Tools;

use Carbon\Core\Settings;

class CLI
{
    private static $short_opts = 'h:p:v';
    private static $long_opts  = array(
        'allow-origins:', 'debug:', 'buffer-size:',
        'max-buffer', 'with-passphrase',
        'enable-tls:', 'enable-ssl:', 'enable-sslv2:',
        'enable-sslv3:', 'help', 'version'
    );

    public static function parse()
    {
        if (false !== ($opts = getopt(self::$short_opts, self::$long_opts))) {
            foreach ($opts as $option => $value) {
                switch ($option) {
                    // host
                    case 'h':
                        Settings::set('server', 'host', $value);
                        break;

                    // port
                    case 'p':
                        Settings::set('server', 'port', $value);
                        break;

                    case 'help':
                        self::usage();
                        break;

                    // version
                    case 'v':
                    case 'version':
                        // stick the version number somewhere globally accessible
                        die('Carbon WebSocket Server v0.1');
                        break;

                    case 'buffer-size':
                    case 'max-buffer':
                        Settings::set('server', 'max_buffer', $value);
                        break;

                    case 'timezone':
                        Settings::set('options', 'default_timezone', $value);

                    case 'allow-origins':
                        $origins = explode(',', $value);

                        if (!empty($origins)) {
                            $origin_arr = array();

                            foreach ($origins as $o) {
                                $origin_arr[] = trim($o);
                            }

                            Settings::set('origins', 'origin_check_enabled', true);
                            Settings::set('origins', 'allowed_origins', $origin_arr);
                        }

                        break;

                    case 'enable-ssl':
                        Settings::set('server', 'scheme', 'ssl');
                        Settings::set('ssl', 'local_cert_path', $value);

                    case 'enable-sslv2':
                        Settings::set('server', 'scheme', 'sslv2');
                        Settings::set('ssl', 'local_cert_path', $value);

                    case 'enable-sslv3':
                        Settings::set('server', 'scheme', 'sslv3');
                        Settings::set('ssl', 'local_cert_path', $value);

                    case 'enable-tls':
                        Settings::set('server', 'scheme', 'tls');
                        Settings::set('ssl', 'local_cert_path', $value);
                        break;

                    case 'with-passphrase':
                        Settings::set('ssl', 'local_cert_passphrase', $value);
                        break;

                }
            }
        }
    }

    private static function usage()
    {
        die("usage: " . $_SERVER['PHP_SELF'] . " [options]
options:
\t-h\t\t\t\tBind the server to a specific host [required]
\t-p\t\t\t\tBind the server to a specific port [required]
\n\t--max-buffer=<size>\t\tDefine the maximimum buffer <size> in bytes
\t--timezone=<tz>\t\t\tValid timezone for server logging
\t--allow-origins=<origins>\tComma-seperated list of origins to accept
\n\t--enable-tls=<cert>\t\tEnable TLS security with a path to a valid <cert>
\t--with-passphrase=<pass>\tIf TLS enabled and cert needs a passphrase
\n\t--version\t\t\tShow version number and exit
\t--help\t\t\t\tShow this help message and exit");
    }

}

?>