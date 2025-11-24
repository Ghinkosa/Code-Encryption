# Laravel Code Protector (no Blade)

This package encrypts PHP files under configured paths and replaces original files with a small stub that triggers runtime decryption.

**How it works**

- `php artisan code:encrypt` encrypts files and writes `.enc` payloads beside originals and replaces original `.php` with a small stub.
- At runtime the stub calls `Galata\CodeProtect\Loader::loadFromStub(__FILE__)`, which decrypts the `.enc` file and `eval()`s the PHP code in memory (no persistent decrypted files by default).

**Install**

1. Place package in `packages/galata/laravel-code-protector` or use path repository in composer.json.
2. `composer require galata/laravel-code-protector:dev-main`
3. `php artisan vendor:publish --tag=config`
4. Set `CODE_PROTECT_KEY` or ensure `APP_KEY` is set.
5. `php artisan code:encrypt`

**Notes**

- This package purposely skips Blade view encryption.
- Keep backups of originals before encrypting.
