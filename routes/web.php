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
    return Response::html(view('welcome', [
        'message'  => 'Welcome to Fly Framework!',
        'features' => ['MVC', 'Routing', 'Middleware', 'Template Engine'],
        'isCool'   => true
    ])->render());
})->name('welcome');

// Middleware Demo
Route::get('/middleware-test', function (Request $request) {
    return Response::json(['message' => 'Middleware passed!']);
})->middleware(\App\Middleware\ExampleMiddleware::class);

// Config Demo
Route::get('/config-test', function () {
    return Response::json([
        'app_name' => config('app.name'),
        'app_env'  => env('APP_ENV'),
        'debug'    => config('app.debug'),
    ]);
});

// Database Demo
Route::get('/db-test', function () {
    // Create Table
    \Fly\Support\Facades\DB::statement('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)');
    
    // Insert Record
    \Fly\Support\Facades\DB::table('users')->insert([
        'id'    => 1,
        'name'  => 'John Doe',
        'email' => 'john@example.com'
    ]);
    
    // Fetch Record
    $users = \Fly\Support\Facades\DB::table('users')->where('name', 'John Doe')->get();
    
    // Cleanup
    \Fly\Support\Facades\DB::statement('DROP TABLE users');
    
    return Response::json(['users' => $users]);
});
