<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController
{
    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['message' => __('auth.invalid_credentials')], 401);
        }

        return $this->respondWithToken(
            user: auth()->user(),
            token: $token,
        );
    }

    public function logout()
    {
        auth()->logout();

        return $this->message('logout.successful', 200);
    }

    public function me(Request $request)
    {
        $user = auth()->user()->with(['roles', 'permissions'])->first();
        return $this->success(new UserResource($user));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:255|unique:users,username,' . Auth::id(),
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('username')) {
            $user->username = $request->username;
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json(['message' => 'Profile updated successfully', 'user' => $user]);
    }

    protected function respondWithToken(User $user, string $token)
    {
        return $this->success([
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
        ]);
    }
}
