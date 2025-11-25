<?php

namespace GalataEth\CodeProtect;

class Encryptor
{
    public static function encryptFile(string $filePath, string $rawKey, string $suffix = '.galo'): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Unable to read file: $filePath");
        }

        $algorithm = config('codeprotect.algorithm', 'AES-256-CBC');
        $ivLen = openssl_cipher_iv_length($algorithm);
        $iv = random_bytes($ivLen);

        $encKey = self::deriveKey($rawKey);

        $ciphertext = openssl_encrypt($content, $algorithm, $encKey, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            throw new \RuntimeException("Encryption failed: $filePath");
        }

        // store IV + cipher, base64 encoded
        file_put_contents($filePath . $suffix, base64_encode($iv . $ciphertext));

        // stub always loads decrypted source at runtime
        $stub = <<<'PHP'
<?php
\GalataEth\CodeProtect\Loader::loadFromStub(__FILE__);
PHP;

        file_put_contents($filePath, $stub);
    }


    public static function decryptPayload(string $payload, string $rawKey): string
    {
        $algorithm = config('codeprotect.algorithm', 'AES-256-CBC');

        $data = base64_decode($payload);
        $ivLen = openssl_cipher_iv_length($algorithm);

        $iv = substr($data, 0, $ivLen);
        $cipher = substr($data, $ivLen);

        $key = self::deriveKey($rawKey);

        $plain = openssl_decrypt($cipher, $algorithm, $key, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            throw new \RuntimeException("Decryption failed");
        }

        return $plain;
    }


    public static function decryptFile(string $encPath, string $rawKey): string
    {
        $payload = file_get_contents($encPath);
        if ($payload === false) {
            throw new \RuntimeException("Cannot read: $encPath");
        }

        return self::decryptPayload($payload, $rawKey);
    }


    public static function deriveKey(string $key): string
    {
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $cfg = config('codeprotect.hkdf');

        return hash_hkdf(
            $cfg['hash'],
            $key,
            $cfg['length'],
            $cfg['info'],
            $cfg['salt']
        );
    }
}
