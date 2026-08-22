# 10. Security Subsystem (Auth, CSRF, JWT & Encryption)

Security is paramount in NexusPHP. The framework includes native suites for Session Authentication, JWT generation, CSRF validation, Password Hashing, and AES-256-GCM Encryption.

---

## 1. Password Hashing (`Nexus\Security\Password`)

Secure password hashing using modern algorithms (Argon2id or Bcrypt):

```php
use Nexus\Security\Password;

// Hash password
$hashedPassword = Password::hash('SecretPass123!');

// Verify password
if (Password::verify('SecretPass123!', $hashedPassword)) {
    // Password matches
}
```

---

## 2. Session Authentication (`Nexus\Security\Auth`)

```php
use Nexus\Security\Auth;

// Attempt login with credentials
if (Auth::attempt(['email' => $email, 'password' => $password])) {
    $user = Auth::user(); // Retrieve authenticated user model instance
    echo "Welcome back, " . $user->name;
}

// Check if authenticated
if (Auth::check()) {
    // User is logged in
}

// Logout user
Auth::logout();
```

---

## 3. JSON Web Tokens (`Nexus\Security\Jwt`)

For stateless RESTful APIs, NexusPHP includes native HMAC-SHA256 JWT parsing and signature verification:

```php
use Nexus\Security\Jwt;

$secretKey = config('app.key');

// Generate JWT Token (Expires in 3600 seconds)
$payload = [
    'sub' => 123,
    'name' => 'Jane Doe',
    'role' => 'admin',
    'exp' => time() + 3600,
];

$token = Jwt::encode($payload, $secretKey);

// Decode and verify JWT Token
try {
    $decodedPayload = Jwt::decode($token, $secretKey);
    echo "Authenticated User ID: " . $decodedPayload['sub'];
} catch (\Exception $e) {
    // Token signature invalid or expired
}
```

---

## 4. Cross-Site Request Forgery (CSRF) (`Nexus\Security\Csrf`)

NexusPHP automatically generates session CSRF tokens to protect form submissions:

### Generating CSRF Input Field in Native View:

```php
<form method="POST" action="/profile">
    <input type="hidden" name="_token" value="<?= e(\Nexus\Security\Csrf::getToken()) ?>">
    <input type="text" name="name" value="Jane">
    <button type="submit">Update</button>
</form>
```

---

## 5. Symmetric Encryption (`Nexus\Security\Encryptor`)

Encrypt sensitive data stored in database fields or cookies using AES-256-GCM:

```php
use Nexus\Security\Encryptor;

$encryptor = new Encryptor(config('app.key'));

// Encrypt plaintext string
$cipherText = $encryptor->encrypt('Sensitive SSN Data');

// Decrypt ciphertext
$plainText = $encryptor->decrypt($cipherText);
```

---

## 6. Next Steps

Learn how to optimize application performance using cache drivers in [11. Caching Subsystem](11-caching.md).
