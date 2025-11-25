<?php

namespace GalataEth\CodeProtect;

class Loader
{
    public static function loadFromStub(string $stubPath): void
    {
        $suffix = config('codeprotect.enc_suffix', '.galo');
        $encPath = $stubPath . $suffix;

        if (!file_exists($encPath)) {
            throw new \RuntimeException("Encrypted file missing: $encPath");
        }

        $rawKey = config('codeprotect.key');
        $php = Encryptor::decryptFile($encPath, $rawKey);

        eval("?>$php");
    }


    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload'], true, true);
    }


    public static function autoload(string $class): void
    {
        $paths = config('codeprotect.paths', ['app/']);
        $suffix = config('codeprotect.enc_suffix', '.galo');

        $rel = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

        foreach ($paths as $p) {
            $stubPath = base_path("$p/$rel");

            // encrypted file
            $encPath = $stubPath . $suffix;

            if (file_exists($encPath)) {
                $rawKey = config('codeprotect.key');
                $php = Encryptor::decryptFile($encPath, $rawKey);
                eval("?>$php");
                return;
            }
        }
    }
}
