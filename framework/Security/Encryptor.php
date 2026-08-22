<?php
declare(strict_types=1);

namespace Nexus\Security;

/**
 * Libsodium Symmetric Authenticated Encryption Wrapper
 */
class Encryptor
{
    protected string $key;

    public function __construct(?string $key = null)
    {
        if ($key === null) {
            $key = env('APP_KEY', 'default_secret_key_32_bytes_len_!!');
        }
        $this->key = hash('sha256', $key, true);
    }

    public function encrypt(string $plainText): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipherText = sodium_crypto_secretbox($plainText, $nonce, $this->key);
        return base64_encode($nonce . $cipherText);
    }

    public function decrypt(string $cipherText): ?string
    {
        $decoded = base64_decode($cipherText, true);
        if ($decoded === false || strlen($decoded) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $encrypted = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        try {
            $decrypted = sodium_crypto_secretbox_open($encrypted, $nonce, $this->key);
            return $decrypted !== false ? $decrypted : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
