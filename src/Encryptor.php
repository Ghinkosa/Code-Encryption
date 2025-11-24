<?php

namespace GalataEth\CodeProtect;

class Encryptor
{
    public static function encryptFile(string $filePath, string $key, string $suffix = '.galo'): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Unable to read file: {$filePath}");
        }

        $algorithm = config('codeprotect.algorithm', 'AES-256-CBC');
        $ivLen = openssl_cipher_iv_length($algorithm);
        $iv = openssl_random_pseudo_bytes($ivLen);

        $encKey = self::deriveKey($key);
        $cipher = openssl_encrypt($content, $algorithm, $encKey, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) throw new \RuntimeException('Encryption failed');

        $payload = base64_encode($iv . $cipher);
        file_put_contents($filePath . $suffix, $payload);

        // Stub that triggers runtime loader
        $stub = <<<'PHP'
<?php
if (!class_exists('\GalataEth\CodeProtect\Loader', false)) {
    @include_once __DIR__ . '/../../vendor/autoload.php';
}
\GalataEth\CodeProtect\Loader::loadFromStub(__FILE__);
PHP;

        file_put_contents($filePath, $stub);
    }

    public static function decryptPayload(string $payload, string $key): string
    {
        $algorithm = config('codeprotect.algorithm', 'AES-256-CBC');
        $decoded = base64_decode($payload, true);
        if ($decoded === false) throw new \RuntimeException('Invalid payload');

        $ivLen = openssl_cipher_iv_length($algorithm);
        $iv = substr($decoded, 0, $ivLen);
        $ciphertext = substr($decoded, $ivLen);

        $plain = openssl_decrypt($ciphertext, $algorithm, $key, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) throw new \RuntimeException('Decryption failed');

        return $plain;
    }

    public static function decryptFile(string $encPath, string $key): string
    {
        $payload = file_get_contents($encPath);
        if ($payload === false) throw new \RuntimeException("Cannot read encrypted file: {$encPath}");
        return self::decryptPayload($payload, $key);
    }

    public static function deriveKey(string $key): string
    {
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $hkdf = config('codeprotect.hkdf', []);
        $info = $hkdf['info'] ?? 'galata-code-protector';
        $salt = $hkdf['salt'] ?? 'static_salt_001';
        $length = $hkdf['length'] ?? 32;
        $hash = $hkdf['hash'] ?? 'sha256';

        if (function_exists('hash_hkdf')) {
            return hash_hkdf($hash, $key, $length, $info, $salt);
        }

        // Manual HKDF
        $prk = hash_hmac($hash, $key, $salt, true);
        $t = '';
        $okm = '';
        $counter = 1;
        while (strlen($okm) < $length) {
            $t = hash_hmac($hash, $t . $info . chr($counter), $prk, true);
            $okm .= $t;
            $counter++;
        }
        return substr($okm, 0, $length);
    }
}
