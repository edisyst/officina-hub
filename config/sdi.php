<?php

return [
    // Attivare solo dopo accreditamento formale AdE (vedere docs/sdi-diretto.md)
    'abilitato'     => env('SDI_ABILITATO', false),

    'canale'        => env('SDI_CANALE', 'sftp'),           // sftp|webservice
    'host'          => env('SDI_SFTP_HOST', ''),
    'port'          => env('SDI_SFTP_PORT', 22),
    'username'      => env('SDI_SFTP_USERNAME', ''),
    'private_key'   => env('SDI_SFTP_PRIVATE_KEY_PATH', ''),
    'remote_in'     => env('SDI_SFTP_DIR_IN', '/ricezione'),
    'remote_out'    => env('SDI_SFTP_DIR_OUT', '/trasmissione'),
    'cert_path'     => env('SDI_CERT_PATH', ''),
    'cert_password' => env('SDI_CERT_PASSWORD', ''),
];
