<?php

return [

    // AES-256 encryption key (defaults to APP_KEY if not set)
    // This value can be raw bytes or a 'base64:...' string. We derive an internal key from it via HKDF.
    'key' => env('CODE_PROTECT_KEY', env('APP_KEY')),

    // folders to encrypt (relative to base_path)
    'paths' => [
        'app/',
    ],

    // encrypted file suffix appended to originals (stub files remain with original name)
    'enc_suffix' => '.galo',

    // encryption algorithm
    'algorithm' => 'AES-256-CBC',

    // HKDF info and salt for key derivation (change to your own values)
    'hkdf' => [
        'info' => 'galata-code-protector',
        'salt' => 'static_salt_001',
        'length' => 32, // bytes
        'hash' => 'sha256',
    ],
];
