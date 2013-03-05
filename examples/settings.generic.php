<?php

return array(
    'server' => array(
        'scheme'        => 'tcp',
        'host'          => '0.0.0.0',
        'port'          => 12345,
        'max_buffer'    => 2048
    ),

    'options' => array(
        'default_timezone'           => 'America/New_York',
        'socket_select_timeout_sec'  => 0,
        'socket_select_timeout_usec' => 200000
    ),

    'debug' => array(
        'debug_verbosity' => 4
    ),

    'ssl' => array(
        'ssl_enabled'           => false,
        'local_cert_pem'        => null,
        'local_cert_passphrase' => null,
        'verify_peer'           => false,
        'allow_self_signed'     => true
    ),

    'origin_check' => array(
        'origin_check_enabled' => false,
        'allow_origins' => array()
    ),

);

?>