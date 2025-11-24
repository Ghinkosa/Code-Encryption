<?php

namespace GalataEth\CodeProtect\Console;

use Illuminate\Console\Command;
use GalataEth\CodeProtect\Encryptor;

class EncryptCommand extends Command
{
    protected $signature = 'code:encrypt {--delete-original : Delete original files after encryption (WARNING)}';
    protected $description = 'Encrypt PHP files in configured paths. Leaves a small stub in the original file that triggers runtime decryption.';

    public function handle()
    {
        $key = config('codeprotect.key');
        if (empty($key)) {
            $this->error('Encryption key not set (CODE_PROTECT_KEY or APP_KEY).');
            return 1;
        }

        $paths  = config('codeprotect.paths', ['app/']);
        $suffix = config('codeprotect.enc_suffix', '.galo');

        // Absolute base path
        $base = base_path() . DIRECTORY_SEPARATOR;

        // 🔥 NEW FIX — Support all your new skip directories
        $skipDirs = [
            'vendor/',
            'storage/',
            'bootstrap/',
            'config/',
            'tests/',
            'routes/',        // added
            'database/',      // added
            'resources/',     // added
        ];

        // 🔥 NEW FIX — Include your new exact files
        $skipFiles = [
            'app/Console/Kernel.php',
            'app/Http/Kernel.php',
            'app/Providers/AppServiceProvider.php',
            'bootstrap/app.php',
            'routes/web.php',           // added
            'routes/api.php',           // added
            'config/app.php',           // added
        ];

        foreach ($paths as $p) {

            $dir = base_path($p);
            if (!is_dir($dir)) {
                $this->warn("Path not found: $dir");
                continue;
            }

            $rii = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($rii as $file) {

                if ($file->isDir()) continue;
                if ($file->getExtension() !== 'php') continue;

                $path = $file->getPathname();

                // Convert absolute file path → relative (important!)
                $relative = str_replace($base, '', $path);

                // Skip directories
                foreach ($skipDirs as $dirSkip) {
                    if (str_starts_with($relative, trim($dirSkip, '/').'/')) {
                        continue 2;
                    }
                }

                // Skip exact files
                if (in_array($relative, $skipFiles, true)) {
                    continue;
                }

                // Never encrypt this package itself
                if (str_contains($relative, 'laravel-code-protector')) {
                    continue;
                }

                // Skip already encrypted files
                if (file_exists($path . $suffix)) {
                    $this->info("Already encrypted (skipping): $relative");
                    continue;
                }

                // Encrypt
                Encryptor::encryptFile($path, $key, $suffix);
                $this->info("Encrypted: $relative");
            }
        }

        $this->info('Done.');
        return 0;
    }
}
