<?php

/*
|--------------------------------------------------------------------------
| Cache Configuration
|--------------------------------------------------------------------------
|
| Configure default cache driver and connection settings.
| Used by Nexus\Cache\CacheManager.
|
*/

return [

    'default' => env('CACHE_DRIVER', 'file'),

    'stores' => [

        'file' => [
            'driver' => 'file',
            'path' => null,
        ],

        'apcu' => [
            'driver' => 'apcu',
        ],

        'redis' => [
            'driver' => 'redis',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => (int) env('REDIS_PORT', 6379),
        ],

    ],

];
