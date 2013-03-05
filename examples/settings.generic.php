<?php

return array(

    'server' => array(
        /**
         * 'tcp', 'ssl', or 'tls'.  Using ssl or tls will activate the 'ssl' section below
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
        'max_buffer'    => 2048
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

    /**
     * Not currently implemented
     */
    'debug' => array(
        'debug_verbosity' => 4
    ),

    /**
     * Not currently implement; Awaiting PHP bug fix
     */
    'ssl' => array(
        'local_cert_pem'        => null,
        'local_cert_passphrase' => null,
        'verify_peer'           => false,
        'allow_self_signed'     => true
    ),

    /**
     * The following sections still need to be written
     */
    'origin_check' => array(
        'origin_check_enabled' => false,
        'allow_origins' => array(
            'is->null',             // is->    : makes a direct comparison
            'has->php-oop.net',     // has->   : makes general comparison (if the origin contains this text)
        )
    ),

    'throttle' => array(
        'max_connections' => 200,
        'max_connections_per_peer' => 3,
        'max_requests_per_min' => 60
    ),

);

?>