<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\Authentication;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'docs'], function () {
    Route::get('/', function () {
        return view('l5-swagger.index');
    });
});

Route::middleware(['throttle:api'])->group(function () {
    Route::controller(AuthController::class)
        ->prefix('auth')
        ->name('auth.')
        ->middleware(['throttle:auth'])
        ->group(function () {
            Route::post('/login', 'login');
            Route::middleware([Authentication::class])->group(function () {
                Route::get('/me', 'me');
                Route::post('/update', 'update');
                Route::post('/logout', 'logout');
            });
        });

    // Users
    Route::middleware([Authentication::class])->group(function () {
        Route::controller(UserController::class)
            ->prefix('users')
            ->name('users.')
            ->middleware(['throttle:users'])
            ->group(function () {
                Route::get('/', 'index')->middleware('checkPermission:show_users');
                Route::get('/{id}', 'show')->middleware('checkPermission:show_users');
                Route::post('/', 'store')->middleware('checkPermission:create_users');
                Route::post('/{id}', 'update')->middleware('checkPermission:edit_users');
                Route::delete('/{id}', 'destroy')->middleware('checkPermission:delete_users');

                Route::post('/{id}/roles', 'assignRoles')->middleware('checkPermission:assign_roles');
                Route::post('/{id}/permissions', 'assignPermissions')->middleware('checkPermission:assign_permissions');
            });
    });

    // Permissions
    Route::middleware([Authentication::class])->group(function () {
        Route::controller(PermissionController::class)
            ->prefix('permissions')
            ->name('permissions.')
            ->middleware(['throttle:permissions'])
            ->group(function () {
                Route::get('/', 'index')->middleware('checkPermission:show_permissions');
                Route::post('/', 'store')->middleware('checkPermission:create_permissions');
                Route::post('/{id}', 'update')->middleware('checkPermission:edit_permissions');
                Route::delete('/{id}', 'destroy')->middleware('checkPermission:delete_permissions');
            });
    });

    // Roles
    Route::middleware([Authentication::class])->group(function () {
        Route::controller(RoleController::class)
            ->prefix('roles')
            ->name('roles.')
            ->middleware(['throttle:roles'])
            ->group(function () {
                Route::get('/', 'index')->middleware('checkPermission:show_roles');
                Route::get('/{id}', 'show')->middleware('checkPermission:show_roles');
                Route::post('/', 'store')->middleware('checkPermission:create_roles');
                Route::post('/{id}', 'update')->middleware('checkPermission:edit_roles');
                Route::delete('/{id}', 'destroy')->middleware('checkPermission:delete_roles');
            });
    });

    // Audit Logs
    Route::middleware([Authentication::class])->group(function () {
        Route::controller(AuditLogController::class)
            ->prefix('audit_logs')
            ->name('audit_logs.')
            ->middleware('throttle:audit_logs')
            ->group(function () {
                Route::get('/', 'index')->middleware('checkRole:super_admin');
            });
    });
});
