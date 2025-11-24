<?php

namespace Galata\CodeProtect\Console;

use Illuminate\Console\Command;
use Galata\CodeProtect\Encryptor;

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

        // decode base64 if provided
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $paths = config('codeprotect.paths', ['app/']);
        $suffix = config('codeprotect.enc_suffix', '.enc');

        foreach ($paths as $p) {
            $dir = base_path($p);
            if (!is_dir($dir)) {
                $this->warn("Path not found: $dir");
                continue;
            }

            $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($rii as $file) {
                if ($file->isDir()) continue;
                if ($file->getExtension() !== 'php') continue;

                $path = $file->getPathname();

                // skip vendor, storage, bootstrap, config, tests
                $skipPatterns = ['vendor' . DIRECTORY_SEPARATOR, 'storage' . DIRECTORY_SEPARATOR, 'bootstrap' . DIRECTORY_SEPARATOR, 'config' . DIRECTORY_SEPARATOR, 'tests' . DIRECTORY_SEPARATOR];
                $skip = false;
                foreach ($skipPatterns as $pat) {
                    if (str_contains($path, $pat)) { $skip = true; break; }
                }
                if ($skip) continue;

                // do not encrypt this package files
                if (str_contains($path, 'laravel-code-protector')) continue;

                // already encrypted stub? check for suffix file
                $enc = $path . $suffix;
                if (file_exists($enc)) {
                    $this->info("Already encrypted (skipping): $path");
                    continue;
                }

                // encrypt
                Encryptor::encryptFile($path, $key, $suffix);
                $this->info("Encrypted: $path");

                if ($this->option('delete-original')) {
                    // if user chose, remove stub and create a tiny loader that still triggers (not recommended)
                    // for safety we won't delete by default
                }
            }
        }

        $this->info('Done.');
        return 0;
    }
}
