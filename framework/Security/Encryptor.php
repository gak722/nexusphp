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
            $config = $this->config();
            $key = $config->get('app.key', env('APP_KEY'));
        }

        if (empty($key) || strlen((string)$key) < 16 || $key === 'default_secret_key_32_bytes_len_!!') {
            throw new \RuntimeException("Application key [APP_KEY] is missing, insecurely configured, or under 16 characters long.");
        }

        $this->key = hash('sha256', (string) $key, true);
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

    protected function config(): \Nexus\Foundation\Config
    {
        try {
            $app = \Nexus\Foundation\Application::getInstance();

            if ($app->has(\Nexus\Foundation\Config::class)) {
                $config = $app->make(\Nexus\Foundation\Config::class);

                if ($config instanceof \Nexus\Foundation\Config) {
                    return $config;
                }
            }
        } catch (\Throwable $e) {
            // Fallback to standalone default Config instance
        }

        return new \Nexus\Foundation\Config();
    }
}
