<?php

/*
|--------------------------------------------------------------------------
| Security Headers
|--------------------------------------------------------------------------
|
| Headers applied to every response by Nexus\Http\Middleware\SecurityHeadersMiddleware.
|
| - Override any value here to change it without touching framework core.
|   Framework defaults are merged in, so you only need to list what changes.
| - Set a header's value to null to prevent it from being sent.
| - Content-Security-Policy may be a raw string or an array of directives,
|   where each directive maps to a string or an array of sources.
|
*/

return [

    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'no-referrer-when-downgrade',

        // Directive array form — allows the CDNs and inline blocks used by
        // resources/views/layouts/docs.php (Tailwind Play CDN, DaisyUI,
        // Google Fonts) while keeping default-src locked to 'self'.
        'Content-Security-Policy' => [
            'default-src' => "'self'",
            'script-src'  => ["'self'", "'unsafe-inline'", 'https://cdn.tailwindcss.com'],
            'style-src'   => ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com', 'https://cdn.jsdelivr.net'],
            'font-src'    => ["'self'", 'https://fonts.gstatic.com'],
            'img-src'     => ["'self'", 'data:'],
        ],
    ],
];
