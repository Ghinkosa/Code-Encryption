<?php

namespace GalataEth\CodeProtect;

class Loader
{
    /**
     * Load and decrypt a stub file
     *
     * @param string $stubPath Path to the stub
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
     * Fallback autoloader for encrypted classes
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
            $fullStub = base_path(rtrim($p, '/') . '/' . $classPath);

            // Require stub if exists
            if (file_exists($fullStub)) {
                require_once $fullStub;
                return;
            }

            // Directly decrypt if only encrypted file exists
            $encPath = $fullStub . $suffix;
            if (file_exists($encPath)) {
                $key = config('codeprotect.key');
                $encKey = Encryptor::deriveKey($key);
                $php = Encryptor::decryptFile($encPath, $encKey);
                eval('?>' . $php);
                return;
            }
        }
    }
}
