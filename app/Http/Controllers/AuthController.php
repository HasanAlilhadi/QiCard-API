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

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Authenticate user and generate token",
     *     description="Login with username and password to get auth token",
     *     operationId="login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password"},
     *             @OA\Property(property="username", type="string", example="admin"),
     *             @OA\Property(property="password", type="string", format="password", example="1234")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     ref="#/components/schemas/User"
     *                 ),
     *                 @OA\Property(property="token", type="string", example="1|laravel_sanctum_token_hash"),
     *                 @OA\Property(property="token_type", type="string", example="bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid Credentials")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Logout user",
     *     description="Invalidate the user's authentication token",
     *     operationId="logout",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/auth/me",
     *     summary="Get authenticated user details",
     *     description="Returns the currently authenticated user's information",
     *     operationId="me",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/User"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Something went wrong.")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/auth/update",
     *     summary="Update authenticated user's profile",
     *     description="Update the current user's name, username, or password",
     *     operationId="updateProfile",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="password", type="string", format="password", example="new_password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/User"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="username",
     *                     type="array",
     *                     @OA\Items(type="string", example="The username has already been taken.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
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
