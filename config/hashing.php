<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default hash driver that will be used to hash
    | passwords for your application. By default, the bcrypt algorithm is
    | used; however, you remain free to modify this option if you wish.
    |
    | Supported: "bcrypt", "argon", "argon2id"
    |
    */

    'driver' => 'bcrypt',

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options for the bcrypt algorithm.
    | The rounds option allows you to control the amount of work that is
    | required to hash a password. A higher cost means more security.
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options for the Argon algorithm.
    | These options control the amount of work that is required to hash
    | a password, as well as the memory and time cost factors.
    |
    */

    'argon' => [
        'memory' => 65536,
        'threads' => 1,
        'time' => 2,
    ],

];
