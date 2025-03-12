<?php

/*
 * TO RUN THE PROJECT
 *
 * - Setup JWT (if needed)
 * --- run composer require tymon/jwt-auth
 * --- run php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
 * --- run php artisan jwt:secret
 * --- In User model add (implements JWTSubject) with its methods
 * --- It's done, you can make AuthController to handle the authentication
 *
 * - Setup Spatie (if needed)
 * --- run composer require spatie/laravel-permission
 * --- run php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
 * --- run php artisan migrate
 * --- In config/permission.php add the Model you want to have Permissions and Roles, like this 'user' => App\Models\User::class, in models array
 */

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Middleware\Authentication;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::middleware([SetLocale::class])->group(function () {
    Route::controller(AuthController::class)
        ->prefix('auth')
        ->name('auth.')
        ->group(function () {
            Route::post('/login', 'login');
            Route::middleware([Authentication::class])->group(function () {
                Route::get('/me', 'me');
                Route::get('/logout', 'logout');
            });
        });

    Route::middleware([Authentication::class])->group(function () {
        Route::controller(UserController::class)
            ->prefix('users')
            ->name('users.')
            ->group(function () {
                Route::get('/filter-users', 'filterUsers');
                Route::get('/', 'index')->middleware('role:welcommer');
                Route::get('/{id}', 'show');
                Route::post('/', 'create');
                Route::post('/bulk-delete', 'bulkDelete');
            });
    });
});


/*
 * - Successful Responses (2xx)
 * --- 200 OK: The request was successful, and the response contains the expected data.
 * --- 201 Created: The resource was successfully created.
 * --- 204 No Content: The request was successful, but there's no content to send in the response.
 *
 * - Client Error Responses (4xx)
 * --- 400 Bad Request: The server could not understand the request due to invalid syntax.
 * --- 401 Unauthorized: Authentication is required and has failed or has not been provided.
 * --- 403 Forbidden: The client does not have access rights to the resource.
 * --- 404 Not Found: The server can't find the requested resource.
 * --- 405 Method Not Allowed: The method used is not supported for the resource.
 * --- 422 Unprocessable Entity: The request was well-formed but contains semantic errors (e.g., validation failed).
 * --- 429 Too Many Requests: The client has sent too many requests in a given amount of time (rate limiting).
 *
 * - Server Error Responses (5xx)
 * --- 500 Internal Server Error: The server encountered an unexpected condition.
 * --- 502 Bad Gateway: The server received an invalid response from an upstream server.
 * --- 503 Service Unavailable: The server is not ready to handle the request (e.g., maintenance).
 * --- 504 Gateway Timeout: The server did not receive a timely response from an upstream server.
 */
