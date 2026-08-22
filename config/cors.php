<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| Configure settings for Cross-Origin Resource Sharing (CORS).
| Settings here are read by Nexus\Http\Middleware\CorsMiddleware.
| Override options below without modifying framework core.
|
*/

return [

    /*
     * Allowed Origins
     *
     * Exact origin strings (e.g. 'https://example.com'), '*' for all origins,
     * or an array of origins.
     */
    'allowed_origins' => ['*'],

    /*
     * Allowed Methods
     *
     * HTTP methods allowed when accessing the resource.
     * Can be specified as an array or comma-separated string.
     */
    'allowed_methods' => [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
    ],

    /*
     * Allowed Headers
     *
     * Headers that are allowed when making cross-origin requests.
     * Can be specified as an array or comma-separated string.
     */
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
    ],

    /*
     * Exposed Headers
     *
     * Whitelist of headers that browsers are allowed to access.
     */
    'exposed_headers' => [],

    /*
     * Max Age (seconds)
     *
     * Indicates how long the results of a preflight request can be cached.
     * Set to 0 or null to omit Access-Control-Max-Age header.
     */
    'max_age' => 0,

    /*
     * Supports Credentials
     *
     * Indicates whether the response to the request can be exposed when
     * the credentials flag is true.
     */
    'supports_credentials' => false,

];
