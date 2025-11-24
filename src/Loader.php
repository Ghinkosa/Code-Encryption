<?php

namespace Galata\CodeProtect;

class Loader
{
    /**
     * Called from stub files. $stubPath is the path to the original stub file.
     * This method finds the corresponding encrypted payload (stubPath + suffix),
     * decrypts it and evaluates PHP code in memory.
     *
     * The stub itself is left in place so Composer autoload works.
     */
    public static function loadFromStub(string $stubPath): void
    {
        $suffix = config('codeprotect.enc_suffix', '.enc');
        $encPath = $stubPath . $suffix;
        if (!file_exists($encPath)) {
            throw new \RuntimeException("Encrypted file not found: {$encPath}");
        }

        $key = config('codeprotect.key');
        if (empty($key)) {
            throw new \RuntimeException('Encryption key not configured (codeprotect.key)');
        }

        // If APP_KEY is base64:..., decode to raw bytes
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        // Decrypt and evaluate
        $php = Encryptor::decryptFile($encPath, $key);
        eval('?>' . $php);
    }

    /**
     * Register an SPL autoloader to try decrypting classes if needed.
     * This is a fallback; primary runtime uses stubs.
     */
    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload'], true, true);
    }

    public static function autoload(string $class): void
    {
        $paths = config('codeprotect.paths', ['app/']);
        $suffix = config('codeprotect.enc_suffix', '.enc');

        $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        foreach ($paths as $p) {
            $fullStub = base_path($p . $classPath);
            if (file_exists($fullStub)) {
                // If file exists and is a stub (we expect it to be), require it so stub calls loadFromStub
                require_once $fullStub;
                return;
            }
            // if stub not present but enc file present, decrypt and eval directly
            $enc = base_path($p . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php' . $suffix);
            if (file_exists($enc)) {
                $key = config('codeprotect.key');
                if (str_starts_with($key, 'base64:')) {
                    $key = base64_decode(substr($key, 7));
                }
                $php = Encryptor::decryptFile($enc, $key);
                eval('?>' . $php);
                return;
            }
        }
    }
}
