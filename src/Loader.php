<?php

namespace GalataEth\CodeProtect;

class Loader
{
    public static function loadFromStub(string $stubPath): void
    {
        $suffix = config('codeprotect.enc_suffix', '.galo');
        $encPath = $stubPath . $suffix;

        if (!file_exists($encPath)) {
            throw new \RuntimeException("Encrypted file not found: {$encPath}");
        }

        $key = config('codeprotect.key');
        if (empty($key)) throw new \RuntimeException('Encryption key not configured (codeprotect.key)');

        $encKey = Encryptor::deriveKey($key);
        $php = Encryptor::decryptFile($encPath, $encKey);

        eval('?>' . $php);
    }

    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload'], true, true);
    }

    public static function autoload(string $class): void
    {
        $paths  = config('codeprotect.paths', ['app/']);
        $suffix = config('codeprotect.enc_suffix', '.galo');
        $classPath = str_replace('\\', '/', $class) . '.php';

        foreach ($paths as $p) {
            $fullPath = base_path($p . $classPath);

            if (file_exists($fullPath)) {
                require_once $fullPath;
                return;
            }

            $enc = $fullPath . $suffix;
            if (file_exists($enc)) {
                $key = config('codeprotect.key');
                $encKey = Encryptor::deriveKey($key);
                $php = Encryptor::decryptFile($enc, $encKey);
                eval('?>' . $php);
                return;
            }
        }
    }
}
