<?php
declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Security & OWASP Headers Configuration
    |--------------------------------------------------------------------------
    |
    | Configure HTTP security headers sent by SecurityHeadersMiddleware.
    | Developers can customize Content Security Policy (CSP) directives without
    | modifying framework core files.
    |
    */

    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'no-referrer-when-downgrade',
    ],

    'csp' => [
        'enabled' => true,

        'directives' => [
            'default-src' => ["'self'"],
            'script-src'  => ["'self'", "'unsafe-inline'", "'unsafe-eval'", "https://cdn.tailwindcss.com"],
            'style-src'   => ["'self'", "'unsafe-inline'", "https://cdn.jsdelivr.net", "https://fonts.googleapis.com"],
            'font-src'    => ["'self'", "https://fonts.gstatic.com"],
            'img-src'     => ["'self'", "data:", "https:"],
            'connect-src' => ["'self'"],
        ],
    ],
];
