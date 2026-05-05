<?php

declare(strict_types=1);

/**
 * Fly Framework - Web Routes
 *
 * Define your application routes here.
 */

use Fly\Routing\Route;
use Fly\Http\Request;
use Fly\Http\Response;

// Homepage
Route::get('/', function () {
    return 'Fly Framework';
});

// JSON response demo
Route::get('/api/status', function () {
    return Response::json([
        'framework' => 'Fly',
        'version'   => '0.1.0',
        'status'    => 'running',
    ]);
});

// HTML response demo
Route::get('/welcome', function () {
    return Response::html('<h1>Welcome to Fly Framework</h1><p>Beautiful, Modern & Opinionated</p>');
});

// Dynamic route + request inspection
Route::get('/users/{id}', function (Request $request, string $id) {
    return Response::json([
        'user_id' => $id,
        'method'  => $request->method(),
        'path'    => $request->path(),
        'ip'      => $request->ip(),
    ]);
});

// POST endpoint demo
Route::post('/api/echo', function (Request $request) {
    return Response::json([
        'received' => $request->all(),
        'is_json'  => $request->isJson(),
    ]);
});
