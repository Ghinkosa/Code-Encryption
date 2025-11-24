<?php

return [

    // AES-256 encryption key (defaults to APP_KEY if not set)
    'key' => env('CODE_PROTECT_KEY', base64_decode(str_replace('base64:', '', env('APP_KEY')))),

    // folders to encrypt (relative to base_path)
    'paths' => [
        'app/',
    ],

    // encrypted file suffix appended to originals (stub files remain with original name)
    'enc_suffix' => '.enc',

    // encryption algorithm
    'algorithm' => 'AES-256-CBC',
];
