# Phase 7: Security Subsystem (Auth, CSRF, JWT, Sodium)

**Duration:** Week 9

---

## 1. What to Build

Phase 7 delivers military-grade security capabilities across stateful and stateless authentication, cross-site request forgery (CSRF) protection, libsodium cryptographic primitives, and token-bucket rate limiting.

### Core Deliverables:

- **`framework/Security/Csrf.php`** — Synchronizer token pattern generating per-session cryptographically secure tokens (`sodium_bin2hex`) and validating incoming header/POST tokens on state-changing HTTP verbs (`POST`, `PUT`, `PATCH`, `DELETE`).
- **`framework/Security/Auth.php`** — Authentication manager with multi-guard support (Stateful Session Guard and Stateless JWT Guard).
- **`framework/Security/Jwt.php`** — Lightweight JSON Web Token builder and verifier using native `libsodium` (`sodium_crypto_sign` / Ed25519) or HMAC-SHA256.
- **`framework/Security/Password.php`** — Password hashing wrapper utilizing native `password_hash()` with `PASSWORD_ARGON2ID` / `PASSWORD_BCRYPT`.
- **`framework/Security/Encryptor.php`** — Symmetric encryption wrapper utilizing `sodium_crypto_secretbox` for sensitive data at rest.
- **`framework/Security/RateLimiter.php`** — Sliding window/Token bucket rate limiter preventing brute-force attacks on sensitive routes.

---

## 2. How Current Implementation Fits with Previous Phase Implementation

- **Middleware Integration:** CSRF check middleware and Rate Limiting middleware plug directly into Phase 1's `MiddlewareStack`.
- **Session & Request Integration:** Integrates with Phase 1's `Request` cookies and headers to extract Bearer tokens and CSRF parameters.
- **ORM Integration:** `Auth::user()` queries Phase 4's `Model` layer to retrieve authenticated user entities.

---

## 3. How to Build

### Step-by-Step Implementation:

1. **`framework/Security/Csrf.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Security;

   use Nexus\Http\Request;
   use Nexus\Http\Response;

   class Csrf
   {
       protected const TOKEN_KEY = '_csrf_token';

       public static function generateToken(): string
       {
           if (session_status() !== PHP_SESSION_ACTIVE) {
               session_start();
           }

           if (empty($_SESSION[self::TOKEN_KEY])) {
               $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
           }

           return $_SESSION[self::TOKEN_KEY];
       }

       public static function validate(Request $request): bool
       {
           if (in_array(strtoupper($request->method), ['GET', 'HEAD', 'OPTIONS'], true)) {
               return true;
           }

           if (session_status() !== PHP_SESSION_ACTIVE) {
               session_start();
           }

           $sessionToken = $_SESSION[self::TOKEN_KEY] ?? null;
           if (!$sessionToken) return false;

           $inputToken = $request->post['_token'] ?? $request->header('X-CSRF-TOKEN') ?? null;
           if (!$inputToken) return false;

           return hash_equals($sessionToken, $inputToken);
       }
   }
   ```

2. **`framework/Security/Jwt.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Security;

   class Jwt
   {
       public static function encode(array $payload, string $secret, int $ttlSeconds = 3600): string
       {
           $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
           $payload['iat'] = time();
           $payload['exp'] = time() + $ttlSeconds;
           $payloadStr = json_encode($payload);

           $base64Header = self::base64UrlEncode($header);
           $base64Payload = self::base64UrlEncode($payloadStr);

           $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $secret, true);
           $base64Signature = self::base64UrlEncode($signature);

           return "{$base64Header}.{$base64Payload}.{$base64Signature}";
       }

       public static function decode(string $jwt, string $secret): ?array
       {
           $parts = explode('.', $jwt);
           if (count($parts) !== 3) return null;

           [$base64Header, [$base64Payload], $base64Signature] = $parts;
           $signature = self::base64UrlDecode($base64Signature);
           $expectedSig = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $secret, true);

           if (!hash_equals($expectedSig, $signature)) {
               return null;
           }

           $payload = json_decode(self::base64UrlDecode($base64Payload), true);
           if (($payload['exp'] ?? 0) < time()) {
               return null; // Expired
           }

           return $payload;
       }

       protected static function base64UrlEncode(string $data): string
       {
           return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
       }

       protected static function base64UrlDecode(string $data): string
       {
           return base64_decode(strtr($data, '-_', '+/'));
       }
   }
   ```

3. **`framework/Security/Encryptor.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Security;

   class Encryptor
   {
       public function __construct(protected string $key) {}

       public function encrypt(string $plainText): string
       {
           $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
           $cipherText = sodium_crypto_secretbox($plainText, $nonce, $this->key);
           return base64_encode($nonce . $cipherText);
       }

       public function decrypt(string $cipherText): ?string
       {
           $decoded = base64_decode($cipherText);
           $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
           $encrypted = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

           $decrypted = sodium_crypto_secretbox_open($encrypted, $nonce, $this->key);
           return $decrypted !== false ? $decrypted : null;
       }
   }
   ```

---

## 4. Success Criteria

- [ ] CSRF middleware blocks state-modifying requests (`POST`, `PUT`, `DELETE`) lacking valid tokens.
- [ ] JWT encoder/decoder verifies signature integrity and enforces timestamp expiration (`exp`).
- [ ] Libsodium Encryptor provides secure AES/Secretbox encryption and decryption.
- [ ] Password hashing uses Argon2id or Bcrypt with proper default work factors.
- [ ] Rate limiter restricts IP/route requests exceeding defined limits with 429 status code.
