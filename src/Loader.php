<?php

namespace GalataEth\CodeProtect;

class Loader
{
    /**
     * Called from stub files.
     * @param string $stubPath Path to the stub file
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

        // Decrypt and eval in memory
        $php = Encryptor::decryptFile($encPath, $encKey);
        eval('?>' . $php);
    }

    /**
     * SPL autoloader fallback for classes.
     * Tries to require stub first, or decrypt & eval encrypted files.
     */
    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload'], true, true);
    }

    public static function autoload(string $class): void
    {
        $paths = config('codeprotect.paths', ['app/']);
        $suffix = config('codeprotect.enc_suffix', '.galo');

        $base = rtrim(base_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Files/directories to skip
        $skipDirs = [
            'vendor/',
            'storage/',
            'bootstrap/',
            'config/',
            'tests/',
        ];

        $skipFiles = [
            'app/Console/Kernel.php',
            'app/Http/Kernel.php',
            'app/Providers/AppServiceProvider.php',
            'bootstrap/app.php',
        ];

        $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

        foreach ($paths as $p) {
            $fullStub = base_path($p . $classPath);

            // Convert absolute path to relative
            $relative = str_replace($base, '', $fullStub);
            $relative = str_replace('\\', '/', $relative);

            // Skip directories
            foreach ($skipDirs as $dirSkip) {
                $dirSkip = rtrim($dirSkip, '/') . '/';
                if (str_starts_with($relative, $dirSkip)) {
                    return; // skip autoloading
                }
            }

            // Skip exact files
            if (in_array($relative, $skipFiles)) {
                return; // skip autoloading
            }

            // If stub exists, require it (stub will call loadFromStub)
            if (file_exists($fullStub)) {
                require_once $fullStub;
                return;
            }

            // If encrypted file exists without stub, decrypt & eval
            $enc = $fullStub . $suffix;
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
