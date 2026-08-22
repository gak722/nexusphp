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

        // Raw string form:
        'Content-Security-Policy' => "default-src 'self'",

        // Directive array form (equivalent to the string above):
        // 'Content-Security-Policy' => [
        //     'default-src' => "'self'",
        //     'script-src'  => ["'self'", 'cdn.example.com'],
        //     'img-src'     => ["'self'", 'data:'],
        // ],
    ],
];
