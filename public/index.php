<?php

declare(strict_types=1);

/**
 * Fly Framework - HTTP Entry Point
 *
 * Runtime Lifecycle:
 *   1. Autoloader → 2. Bootstrap → 3. Load Routes → 4. Kernel Handle
 *
 * Every request to the application enters through this file.
 */

// 1. Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// 2. Bootstrap the application
$app = require __DIR__ . '/../bootstrap/app.php';

// 3. Load route definitions
require __DIR__ . '/../routes/web.php';

// 4. Create the HTTP Kernel and handle the request
$kernel = new Fly\Kernel\HttpKernel($app);
$kernel->handle();
