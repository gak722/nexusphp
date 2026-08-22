<?php

/*
|--------------------------------------------------------------------------
| Queue Configuration
|--------------------------------------------------------------------------
|
| Driver settings for asynchronous worker queues.
| Used by Nexus\Queue\QueueManager.
|
*/

return [

    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
        ],

        'redis' => [
            'driver' => 'redis',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => (int) env('REDIS_PORT', 6379),
            'queue' => 'default',
        ],

    ],

];
