<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAuthRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController
{
    protected $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function login(Request $request)
    {
        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            if ($user) {
                $this->auditService->logAuthActivity(
                    'login_failed',
                    $user->id,
                    [
                        'username' => $request->username,
                        'ip_address' => request()->ip()
                    ]
                );
            } else {
                $this->auditService->logAuthActivity(
                    'login_failed',
                    null,
                    [
                        'attempted_username' => $request->username,
                        'ip_address' => request()->ip()
                    ]
                );
            }

            return response()->json(['message' => 'Invalid Credentials'], 401);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        $this->auditService->logAuthActivity(
            'login_success',
            $user->id,
            [
                'username' => $user->username,
                'ip_address' => request()->ip()
            ]
        );

        return $this->respondWithToken(
            user: $user,
            token: $token,
        );
    }

    public function logout()
    {
        $user = auth()->user();

        $this->auditService->logAuthActivity(
            'logout',
            $user->id,
            [
                'username' => $user->username,
                'ip_address' => request()->ip()
            ]
        );

        $user->currentAccessToken()->delete();

        return $this->message('Logged out successfully.', 200);
    }

    public function me(Request $request)
    {
        $user = User::with(['roles', 'permissions'])->find(Auth::id());

        if (!$user) {
            $this->auditService->logAuthActivity(
                'session_invalid',
                null,
                [
                    'token_id' => auth()->user()?->currentAccessToken()?->id,
                    'ip_address' => request()->ip()
                ]
            );

            $this->logout();
            return $this->notFound('Something went wrong.');
        }

        return $this->success(UserResource::make($user));
    }

    public function update(UpdateAuthRequest $request)
    {
        $user = auth()->user();

        $originalData = [
            'name' => $user->name,
            'username' => $user->username,
        ];

        $changes = [];

        if ($request->has('name')) {
            $user->name = $request->get('name');
            $changes['name'] = $user->name;
        }

        if ($request->has('username')) {
            $user->username = $request->get('username');
            $changes['username'] = $user->username;
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->get('password'));
            $changes['password_changed'] = true;
        }

        $user->save();

        $this->auditService->logAuthActivity(
            'profile_updated',
            $user->id,
            [
                'previous_state' => $originalData,
                'changes' => $changes,
                'ip_address' => request()->ip()
            ]
        );

        return $this->success(UserResource::make($user));
    }

    protected function respondWithToken(User $user, string $token)
    {
        return $this->success([
            'user' => UserResource::make($user),
            'token' => $token,
            'token_type' => 'bearer',
        ]);
    }
}
