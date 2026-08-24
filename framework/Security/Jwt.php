<?php
declare(strict_types=1);

namespace Nexus\Security;

/**
 * Lightweight JSON Web Token Builder and Verifier (HS256)
 */
class Jwt
{
    public static function encode(array $payload, string $secret, int $ttlSeconds = 3600): string
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload['iat'] = time();
        $payload['exp'] = time() + $ttlSeconds;
        $payloadStr = json_encode($payload);

        $base64Header = self::base64UrlEncode((string) $header);
        $base64Payload = self::base64UrlEncode((string) $payloadStr);

        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $secret, true);
        $base64Signature = self::base64UrlEncode($signature);

        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }

    public static function decode(string $jwt, string $secret, array $options = []): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        [$base64Header, $base64Payload, $base64Signature] = $parts;
        
        $header = json_decode(self::base64UrlDecode($base64Header), true);
        if (!is_array($header) || ($header['alg'] ?? null) !== 'HS256') {
            return null; // Reject non-HS256 or invalid algorithm headers
        }

        $signature = self::base64UrlDecode($base64Signature);
        $expectedSig = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $secret, true);

        if (!hash_equals($expectedSig, $signature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        if (!is_array($payload)) {
            return null;
        }

        $leeway = (int) ($options['leeway'] ?? 0);
        $now = time();

        if (isset($payload['exp']) && ($payload['exp'] + $leeway) < $now) {
            return null; // Expired
        }

        if (isset($payload['nbf']) && ($payload['nbf'] - $leeway) > $now) {
            return null; // Not before time not reached
        }

        if (isset($options['issuer']) && ($payload['iss'] ?? null) !== $options['issuer']) {
            return null; // Issuer mismatch
        }

        if (isset($options['audience']) && ($payload['aud'] ?? null) !== $options['audience']) {
            return null; // Audience mismatch
        }

        return $payload;
    }

    protected static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}
