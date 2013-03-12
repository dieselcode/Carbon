<?php

return array(

    'server' => array(
        /**
         * 'tcp', 'ssl', 'sslv2', 'sslv3', or 'tls'.  Using ssl or tls will activate the 'ssl' section below
         * 'tls' is recommended for secure connections
         */
        'scheme'        => 'tcp',

        /**
         * Recommended; host IP to bind server to
         */
        'host'          => '0.0.0.0',

        /**
         * Recommended; host port to bind server to
         */
        'port'          => 12345,

        /**
         * Recommended; See /examples/test_upload.php for overriding this setting for file uploads
         */
        'max_buffer'    => 8192
    ),

    'options' => array(
        /**
         * Default timezone to set for server
         * @link http://www.php.net/manual/en/timezones.php
         */
        'default_timezone'           => 'America/New_York',

        /**
         * The following are recommended settings.  Override at your own risk.
         */
        'socket_select_timeout_sec'  => 0,
        'socket_select_timeout_usec' => 200000
    ),

    'origins' => array(
        'origin_check_enabled' => false,
        'allowed_origins' => array(
            'null',
            'localhost',
            '127.0.0.1',
            'www.php-oop.net',
            'php-oop.net',
            'www.coinfront.com',
            'coinfront.com',
            'www.websocket.org',
            'websocket.org',
        )
    ),

    'ssl' => array(
        'local_cert_path'       => 'carbon.ssl.pem',
        //'local_cert_path'       => 'cf.pem',
        'local_cert_passphrase' => 'carbon',
        //'local_cert_passphrase' => '',
    ),

    /**
     * Not currently implemented
     */
    'debug' => array(
        'debug_verbosity' => 4
    ),

    'throttle' => array(
        'max_connections' => 200,
        'max_connections_per_peer' => 3,
        'max_requests_per_min' => 60
    ),

);

?>