
# 🔐 Laravel Code Protector  
### Secure Laravel/PHP Code Encryption Package  
**By Galata Hinkosa (galata-eth)**

Laravel Code Protector is a powerful package designed to **encrypt your PHP source code**, generate **secure runtime stubs**, and **protect your intellectual property** when deploying Laravel applications.

This package ensures your project runs normally while preventing anyone from accessing or modifying your actual PHP code.

---

## 🏆 Features

- ✔ **AES-256-CBC encryption** for all PHP source files  
- ✔ Generates `.galo` encrypted payloads  
- ✔ Replaces real code with tiny *loader stubs*  
- ✔ Decrypts only in memory (`eval`)  
- ✔ Original code never gets exposed  
- ✔ Works on **Windows, Linux, macOS**  
- ✔ Compatible with Laravel 8, 9, 10, 11  
- ✔ Skips Blade files to avoid breaking views  
- ✔ Secure, fast, and production-ready  

---

## 📦 Installation

### 1. Install via Composer

```bash
composer require galata-eth/laravel-code-protector

# Laravel Code Protector (no Blade)

This package encrypts PHP files under configured paths and replaces original files with a small stub that triggers runtime decryption.

**How it works**

- `php artisan code:encrypt` encrypts files and writes `.galo` payloads beside originals and replaces original `.php` with a small stub.
- At runtime the stub calls `GalataEth\CodeProtect\Loader::loadFromStub(__FILE__)`, which decrypts the `.galo` file and `eval()`s the PHP code in memory (no persistent decrypted files by default).

**Install**
1. Take backup.
2. Place package in `packages/galata-eth/laravel-code-protector` or use path repository in composer.json.
3. `composer require galata-eth/laravel-code-protector:dev-main`
4. `php artisan vendor:publish --tag=config`
5. Set `CODE_PROTECT_KEY` or ensure `APP_KEY` is set.
6. `php artisan code:encrypt`

**Notes**

- This package purposely skips Blade view encryption.
- Keep backups of originals before encrypting.
=======
# Code-Encryption
Laravel code Encription
