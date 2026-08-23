<?php
declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Mailer Transport
    |--------------------------------------------------------------------------
    */
    'default' => env('MAIL_MAILER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    */
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example Application'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    */
    'mailers' => [
        'smtp' => [
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => (int) env('MAIL_PORT', 587),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'timeout' => 15,
        ],
        'log' => [
            'path' => env('MAIL_LOG_PATH', null),
        ],
        'array' => [],
    ],
];
