<?php

declare(strict_types=1);

/**
 * Fly Framework - Web Routes
 */

use Fly\Routing\Route;
use Fly\Http\Request;
use Fly\Http\Response;

// Homepage
Route::get('/', function () {
    return 'Fly Framework';
})->name('home');

// Named route with constraint
Route::get('/users/{id}', function (Request $request, string $id) {
    return Response::json([
        'user_id' => $id,
        'method'  => $request->method(),
        'path'    => $request->path(),
        'ip'      => $request->ip(),
    ]);
})->name('users.show')->whereNumber('id');

// Optional parameter
Route::get('/posts/{slug}/{page?}', function (Request $request, string $slug, string $page = '1') {
    return Response::json(['slug' => $slug, 'page' => (int) $page]);
})->name('posts.show');

// Route group with prefix
Route::group(['prefix' => '/api'], function () {
    Route::get('/status', function () {
        return Response::json([
            'framework' => 'Fly',
            'version'   => '0.1.0',
            'status'    => 'running',
        ]);
    })->name('api.status');

    Route::post('/echo', function (Request $request) {
        return Response::json([
            'received' => $request->all(),
            'is_json'  => $request->isJson(),
        ]);
    })->name('api.echo');
});

// HTML response
Route::get('/welcome', function () {
    return Response::html('<h1>Welcome to Fly Framework</h1><p>Beautiful, Modern & Opinionated</p>');
})->name('welcome');
