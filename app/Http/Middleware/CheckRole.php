<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        try {
            $roleArray = explode(',', $roles);
            if (!Role::whereIn('name', $roleArray)->exists()) {
                throw new UnauthorizedException(403, 'Not authorized.');
            }

            $hasAnyRole = collect($roleArray)->contains(
                fn ($role) => auth()->user()->hasRole(trim($role))
            );

            if (!$hasAnyRole) {
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
