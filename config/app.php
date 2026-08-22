<?php

/*
|--------------------------------------------------------------------------
| Application Configuration
|--------------------------------------------------------------------------
|
| General application configuration settings used across the framework.
|
*/

return [

    /*
    | Application Environment & Debug Mode
    */
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'key' => env('APP_KEY', 'default_secret_key_32_bytes_len_!!'),

    /*
    | Exception & Logging Settings
    */
    'log_path' => env('LOG_PATH', null),

];
