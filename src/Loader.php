<?php

namespace GalataEth\CodeProtect;

class Loader
{
    /**
     * Register the encrypted file autoloader.
     */
    public static function registerAutoloader(): void
    {
        spl_autoload_register([static::class, 'autoload'], true, true);
    }

    /**
     * Autoload encrypted PHP classes.
     */
    public static function autoload(string $class): void
    {
        $paths  = config('codeprotect.paths', ['app/']);
        $suffix = config('codeprotect.enc_suffix', '.galo');

        $base = base_path() . DIRECTORY_SEPARATOR;

        // 🔥 SAME EXCLUSIONS AS EncryptCommand
        $skipDirs = [
            'vendor/',
            'storage/',
            'bootstrap/',
            'config/',
            'tests/',
            'routes/',
            'database/',
            'resources/',
        ];

        // 🔥 Exact files to skip autoloading (never encrypted)
        $skipFiles = [
            'app/Console/Kernel.php',
            'app/Http/Kernel.php',
            'app/Providers/AppServiceProvider.php',
            'bootstrap/app.php',
            'routes/web.php',
            'routes/api.php',
            'config/app.php',
        ];

        // Convert class namespace → path
        $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

        foreach ($paths as $p) {

            $relative = $p . $classPath;
            $relative = ltrim($relative, DIRECTORY_SEPARATOR);

            // Skip exact files
            if (in_array($relative, $skipFiles, true)) {
                return;
            }

            // Skip excluded directories
            foreach ($skipDirs as $d) {
                $d = rtrim($d, '/') . '/';
                if (str_starts_with($relative, $d)) {
                    return;
                }
            }

            // Full path
            $fullPath = base_path($relative);

            // If stub file exists, load it normally
            if (file_exists($fullPath)) {
                require_once $fullPath;
                return;
            }

            // Check for encrypted file
            $encPath = $fullPath . $suffix;

            if (file_exists($encPath)) {
                $key = config('codeprotect.key');
                $encKey = Encryptor::deriveKey($key);
                $php = Encryptor::decryptFile($encPath, $encKey);

                // Evaluate the decrypted class code
                eval('?>' . $php);
                return;
            }
        }
    }
}
