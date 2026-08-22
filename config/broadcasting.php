<?php

/*
|--------------------------------------------------------------------------
| Event Broadcasting Configuration
|--------------------------------------------------------------------------
|
| Configuration for real-time pub/sub event broadcasting.
| Used by Nexus\Events\BroadcastManager.
|
*/

return [

    'default' => env('BROADCAST_DRIVER', 'redis'),

    'connections' => [

        'redis' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => (int) env('REDIS_PORT', 6379),
        ],

    ],

];
