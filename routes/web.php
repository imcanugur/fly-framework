<?php

declare(strict_types=1);

/**
 * Fly Framework - Web Routes
 *
 * Define your application routes here.
 * All routes are registered on the Router singleton via the Route facade.
 */

use Fly\Routing\Route;

Route::get('/', function () {
    return 'Fly Framework';
});
