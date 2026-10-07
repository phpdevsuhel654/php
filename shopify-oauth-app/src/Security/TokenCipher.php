<?php

declare(strict_types=1);

namespace App\Security;

use RuntimeException;

final class TokenCipher
{
    private string $key;

    public function __construct(string $base64Key)
    {
        if ($base64Key === '') {
            throw new RuntimeException('APP_ENCRYPTION_KEY is not configured.');
        }

        $key = base64_decode($base64Key, true);
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('APP_ENCRYPTION_KEY must be a base64-encoded 32-byte key.');
        }

        $this->key = $key;
    }

    public function encrypt(string $plaintext): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipherText = sodium_crypto_secretbox($plaintext, $nonce, $this->key);

        return base64_encode($nonce . $cipherText);
    }

    public function decrypt(string $encoded): string
    {
        $decoded = base64_decode($encoded, true);
        if ($decoded === false || strlen($decoded) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new RuntimeException('Invalid encrypted token payload.');
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipherText = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plaintext = sodium_crypto_secretbox_open($cipherText, $nonce, $this->key);

        if ($plaintext === false) {
            throw new RuntimeException('Failed to decrypt token; key mismatch or data was tampered with.');
        }

        return $plaintext;
    }
}
