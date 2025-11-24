<?php

namespace GalataEth\CodeProtect;

class Loader
{
    /**
     * Called from stub files. $stubPath is the path to the stub file.
     * Finds the encrypted payload and evaluates it.
     */
    public static function loadFromStub(string $stubPath): void
    {
        $suffix = config('codeprotect.enc_suffix', '.galo');
        $encPath = $stubPath . $suffix;

        if (!file_exists($encPath)) {
            throw new \RuntimeException("Encrypted file not found: {$encPath}");
        }

        $key = config('codeprotect.key');
        if (empty($key)) {
            throw new \RuntimeException('Encryption key not configured (codeprotect.key)');
        }

        $encKey = Encryptor::deriveKey($key);

        $php = Encryptor::decryptFile($encPath, $encKey);
        eval('?>' . $php);
    }

    /**
     * Register a fallback autoloader.
     * Tries to decrypt classes if needed.
     */
    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload'], true, true);
    }

    public static function autoload(string $class): void
    {
        $paths = config('codeprotect.paths', ['app/']);
        $suffix = config('codeprotect.enc_suffix', '.galo');

        $classPath = str_replace('\\', '/', $class) . '.php';

        foreach ($paths as $p) {
            $fullPath = base_path($p . $classPath);

            // Require stub if exists
            if (file_exists($fullPath)) {
                require_once $fullPath;
                return;
            }

            // Directly decrypt & eval if only encrypted file exists
            $enc = base_path($p . $classPath . $suffix);
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
