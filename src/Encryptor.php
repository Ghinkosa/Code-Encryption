<?php

namespace Galata\CodeProtect;

class Encryptor
{
    public static function encryptFile(string $filePath, string $key, string $suffix = '.enc'): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Unable to read file: {$filePath}");
        }

        $algorithm = config('codeprotect.algorithm', 'AES-256-CBC');
        $ivLen = openssl_cipher_iv_length($algorithm);
        $iv = openssl_random_pseudo_bytes($ivLen);

        $cipher = openssl_encrypt($content, $algorithm, $key, OPENSSL_RAW_DATA, $iv);
        $payload = base64_encode($iv . $cipher);

        $encFile = $filePath . $suffix;
        file_put_contents($encFile, $payload);

        // Write a small stub into original file that triggers runtime loader
        $stub = <<<'PHP'
<?php
// Encrypted stub - DO NOT EDIT
if (!class_exists('\Galata\CodeProtect\Loader', false)) {
    @include_once __DIR__ . '/../../vendor/autoload.php';
}
\Galata\CodeProtect\Loader::loadFromStub(__FILE__);
PHP;

        file_put_contents($filePath, $stub);
    }

    public static function decryptPayload(string $payload, string $key): string
    {
        $algorithm = config('codeprotect.algorithm', 'AES-256-CBC');
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            throw new \RuntimeException('Invalid payload');
        }

        $ivLen = openssl_cipher_iv_length($algorithm);
        $iv = substr($decoded, 0, $ivLen);
        $ciphertext = substr($decoded, $ivLen);

        $plain = openssl_decrypt($ciphertext, $algorithm, $key, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            throw new \RuntimeException('Decryption failed');
        }
        return $plain;
    }

    public static function decryptFile(string $encPath, string $key): string
    {
        $payload = file_get_contents($encPath);
        if ($payload === false) {
            throw new \RuntimeException("Cannot read encrypted file: {$encPath}");
        }
        return self::decryptPayload($payload, $key);
    }
}
