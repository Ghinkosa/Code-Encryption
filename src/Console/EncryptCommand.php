<?php

namespace GalataEth\CodeProtect\Console;

use Illuminate\Console\Command;
use GalataEth\CodeProtect\Encryptor;

class EncryptCommand extends Command
{
    protected $signature = 'code:encrypt {--delete-original : Delete original files after encryption (WARNING)}';
    protected $description = 'Encrypt PHP files in configured paths. Leaves a small stub that triggers runtime decryption.';

    public function handle()
    {
        $key = config('codeprotect.key');
        if (empty($key)) {
            $this->error('Encryption key not set (CODE_PROTECT_KEY or APP_KEY).');
            return 1;
        }

        $paths  = config('codeprotect.paths', ['app/']);
        $suffix = config('codeprotect.enc_suffix', '.galo');

        $base = str_replace('\\', '/', rtrim(base_path(), DIRECTORY_SEPARATOR)) . '/';

        $skipDirs = ['vendor/', 'storage/', 'bootstrap/', 'config/', 'tests/'];
        $skipFiles = [
            'app/Console/Kernel.php',
            'app/Http/Kernel.php',
            'app/Providers/AppServiceProvider.php',
            'bootstrap/app.php',
        ];
        $skipFiles = array_map(fn($f) => str_replace('\\', '/', $f), $skipFiles);

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

                $path = str_replace('\\', '/', $file->getPathname());
                $relative = str_replace($base, '', $path);

                // Skip directories
                foreach ($skipDirs as $dirSkip) {
                    $dirSkip = rtrim($dirSkip, '/') . '/';
                    if (str_starts_with($relative, $dirSkip)) {
                        continue 2;
                    }
                }

                // Skip exact files
                if (in_array($relative, $skipFiles)) continue;

                // Skip the package itself
                if (str_contains($relative, 'laravel-code-protector')) continue;

                // Skip if already encrypted
                if (file_exists($path . $suffix)) {
                    $this->info("Already encrypted: $relative");
                    continue;
                }

                Encryptor::encryptFile($path, $key, $suffix);
                $this->info("Encrypted: $relative");
            }
        }

        $this->info('Done.');
        return 0;
    }
}
