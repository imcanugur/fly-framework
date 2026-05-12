<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */
    'name' => env('APP_NAME', 'Fly Framework'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    */
    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    */
    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    */
    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        \Fly\Database\DatabaseServiceProvider::class,
        \Fly\View\ViewServiceProvider::class,
        \Fly\Events\EventServiceProvider::class,
        \Fly\Queue\QueueServiceProvider::class,
        \Fly\Cache\CacheServiceProvider::class,
        \Fly\Session\SessionServiceProvider::class,
        \Fly\Auth\AuthServiceProvider::class,
        \App\Providers\EventServiceProvider::class,
        \App\Providers\AppServiceProvider::class,
    ],

];
