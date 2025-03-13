<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        try {
            $permissionArray = explode(',', $permissions);

            if (!Permission::whereIn('name', $permissionArray)->exists()) {
                throw new UnauthorizedException(403, 'Not authorized.');
            }

            $hasAllPermissions = collect($permissionArray)->every(
                fn ($permission) => auth()->user()->hasPermissionTo(trim($permission))
            );

            if (!$hasAllPermissions) {
                throw new UnauthorizedException(403, 'Not authorized.');
            }
            return $next($request);
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 403
            ], 403);
        }
    }
}
