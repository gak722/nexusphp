<?php
declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default HTTP Client Options
    |--------------------------------------------------------------------------
    */
    'timeout' => 30,
    'connect_timeout' => 10,
    'verify_ssl' => true,
    'user_agent' => 'NexusPHP-HttpClient/1.0',
    'max_redirects' => 5,
];
