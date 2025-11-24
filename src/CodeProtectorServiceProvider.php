<?php

namespace GalataEth\CodeProtect;

use Illuminate\Support\ServiceProvider;
use GalataEth\CodeProtect\Console\EncryptCommand;

class CodeProtectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/codeprotect.php', 'codeprotect');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([EncryptCommand::class]);

            $this->publishes([
                __DIR__ . '/../config/codeprotect.php' => config_path('codeprotect.php'),
            ], 'config');
        }

        Loader::register();
    }
}
