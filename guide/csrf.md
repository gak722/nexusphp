# CSRF Protection

NexusPHP automatically protects your application from Cross-Site Request Forgery (CSRF) attacks. CSRF is a type of malicious exploit whereby unauthorized commands are performed on behalf of an authenticated user.

---

## The CSRF Token

NexusPHP automatically generates a unique "synchronizer token" for each active user session. This token is used to verify that the authenticated user is the one actually making the requests to the application.

To generate or retrieve the token, use the `Csrf::generateToken()` method:

```php
use Nexus\Security\Csrf;

$token = Csrf::generateToken();
```

### Form Inclusion

Any time you define a standard HTML form that performs a `POST`, `PUT`, `PATCH`, or `DELETE` request, you must include a hidden `_token` CSRF field in the form so that the CSRF protection middleware can validate the request.

```html
<form method="POST" action="/profile">
    <input type="hidden" name="_token" value="<?php echo e(\Nexus\Security\Csrf::generateToken()); ?>">
    
    <!-- form inputs... -->
    <button type="submit">Save Profile</button>
</form>
```

### X-CSRF-TOKEN Header

In addition to checking for the CSRF token as a `POST` parameter, the `Nexus\Security\Csrf` validator also checks for the `X-CSRF-TOKEN` request header. This is particularly useful for JavaScript/AJAX applications (like Vue or React) that communicate with your API.

If your SPA is running on the same domain and using session-based authentication, ensure your Axios/Fetch client appends this header:

```javascript
fetch('/api/profile', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({ name: 'Alice' })
});
```

---

## Validation Behavior

The `Csrf::validate()` method automatically ignores "read-only" HTTP methods (`GET`, `HEAD`, `OPTIONS`).

For state-mutating requests (`POST`, `PUT`, `PATCH`, `DELETE`), if the token provided in the payload or headers does not exactly match the securely generated token stored in the user's `$_SESSION`, the framework will reject the request.

This protection is typically enforced automatically via the `VerifyCsrfToken` middleware in your HTTP Kernel's web middleware group.
