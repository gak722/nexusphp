# Copilot Spec: Phase 7 — Security Subsystem (Auth, CSRF, JWT, Libsodium, Rate Limiter)

## Objective
Implement stateful session/cookie auth, stateless JWT authentication (`Ed25519`/`HS256`), CSRF protection, password hashing (`Argon2id`/`Bcrypt`), Libsodium secretbox encryption, and token-bucket rate limiting.

## Target Files to Create / Modify
- `framework/Security/Csrf.php`
- `framework/Security/Auth.php`
- `framework/Security/Jwt.php`
- `framework/Security/Password.php`
- `framework/Security/Encryptor.php`
- `framework/Security/RateLimiter.php`

---

## Detailed Specifications

### 1. `framework/Security/Csrf.php`
- Generates 32-byte cryptographically secure session token.
- Validates incoming `POST` parameter `_token` or `X-CSRF-TOKEN` header using `hash_equals()`.

### 2. `framework/Security/Jwt.php`
- `encode(array $payload, string $secret, int $ttlSeconds = 3600): string`
- `decode(string $jwt, string $secret): ?array` — validates HMAC signature and checks `exp` timestamp.

### 3. `framework/Security/Encryptor.php`
- Uses `sodium_crypto_secretbox` and `random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES)`.

---

## Copilot Validation Rules
- [ ] Direct string comparisons on tokens or passwords are strictly forbidden. Always use `hash_equals()`.
- [ ] Cryptographic keys MUST be read from `.env` (`APP_KEY`). Never hardcode secrets.
