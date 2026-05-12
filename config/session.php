<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default session "driver" that will be used on
    | requests. By default, we will use the lightweight native driver but
    | you may specify any of the other wonderful drivers listed here.
    |
    | Supported: "file", "cookie", "database", "redis", "array"
    |
    */

    'driver' => env('SESSION_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that you wish the session
    | to be allowed to remain idle before it expires. If you want them
    | to immediately expire on the browser closing, set that option.
    |
    */

    'lifetime' => env('SESSION_LIFETIME', 120),

    'expire_on_close' => false,

    /*
    |--------------------------------------------------------------------------
    | Session File Location
    |--------------------------------------------------------------------------
    |
    | When using the native session driver, we need a location where session
    | files may be stored. A default has been set for you but a different
    | location may be specified. This is only needed for "file" sessions.
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Configuration
    |--------------------------------------------------------------------------
    |
    | The following options configure the cookie used to identify a session
    | instance on the server. The cookie name, domain, and path as well
    | as the "secure" option may be set here for your convenience.
    |
    */

    'cookie' => env('SESSION_COOKIE', str_replace(' ', '_', strtolower(env('APP_NAME', 'fly_session')))),

    'path' => '/',

    'domain' => env('SESSION_DOMAIN'),

    'secure' => env('SESSION_SECURE', false),

    'http_only' => true,

    'same_site' => 'lax',

];
