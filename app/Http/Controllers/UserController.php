<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignPermissionsRequest;
use App\Http\Requests\AssignRolesRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends BaseController
{
    protected $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * @OA\Get(
     *     path="/users",
     *     summary="Get list of users",
     *     description="Returns a list of all users with their roles and permissions",
     *     operationId="getUsers",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/User")
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
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not authorized."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     )
     * )
     */
    public function index()
    {
        $query = User::query()->with(['roles', 'permissions', 'creator', 'updater']);

        $users = $query->get();
        return $this->success(UserResource::collection($users));
    }

    /**
     * @OA\Get(
     *     path="/users/{id}",
     *     summary="Get user details",
     *     description="Returns details for a specific user by ID",
     *     operationId="getUserById",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of user to return",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
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
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not authorized."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found.")
     *         )
     *     )
     * )
     */
    public function show(int $id)
    {
        $user = User::find($id);

        if (!$user || $user->super_admin) {
            return $this->notFound('User not found.');
        }

        $user = $user->load(['roles', 'permissions', 'creator', 'updater']);

        return $this->success(UserResource::make($user));
    }

    /**
     * @OA\Post(
     *     path="/users",
     *     summary="Create a new user",
     *     description="Creates a new user with the provided information",
     *     operationId="createUser",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","username","password"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User has been created."),
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
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not authorized."),
     *             @OA\Property(property="status", type="integer", example=403)
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
    public function store(StoreUserRequest $request)
    {
        DB::beginTransaction();
        $authUser = auth()->user();

        $user = User::create([
            'name' => $request->get('name'),
            'username' => $request->get('username'),
            'password' => $request->get('password'),
        ]);

        $this->auditService->logUserActivity(
            'user_created',
            $authUser->id,
            $user->id,
            null,
            [
                'name' => $user->name,
                'username' => $user->username,
            ]
        );

        DB::commit();

        $user = $user->load(['roles', 'permissions', 'creator', 'updater']);
        return $this->success(
            UserResource::make($user),
            'User has been created.'
        );
    }

    /**
     * @OA\Post(
     *     path="/users/{id}",
     *     summary="Update a user",
     *     description="Updates a user's information by ID",
     *     operationId="updateUser",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of user to update",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Name"),
     *             @OA\Property(property="username", type="string", example="updated_username"),
     *             @OA\Property(property="password", type="string", format="password", example="new_password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User has been updated."),
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
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not authorized."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found.")
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
    public function update(UpdateUserRequest $request, int $id)
    {
        $user = User::query()->where('super_admin', '=',false)->find($id);

        if (!$user) {
            return $this->notFound('User not found.');
        }

        DB::beginTransaction();
        $authUser = auth()->user();

        $originalData = [
            'name' => $user->name,
            'username' => $user->username,
        ];

        if ($request->has('name')) {
            $user->name = $request->get('name');
        }

        if ($request->has('username')) {
            $user->username = $request->get('username');
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->get('password'));
            $passwordChanged = true;
        } else {
            $passwordChanged = false;
        }

        $user->save();

        $this->auditService->logUserActivity(
            'user_updated',
            $authUser->id,
            $user->id,
            $originalData,
            [
                'name' => $user->name,
                'username' => $user->username,
                'password_changed' => $passwordChanged,
            ]
        );

        DB::commit();

        return $this->success(
            UserResource::make($user->load(['roles', 'permissions', 'creator', 'updater'])),
            'User has been updated.'
        );
    }

    /**
     * @OA\Delete(
     *     path="/users/{id}",
     *     summary="Delete a user",
     *     description="Deletes a user by ID",
     *     operationId="deleteUser",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of user to delete",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User has been deleted.")
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
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not authorized."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found.")
     *         )
     *     )
     * )
     */
    public function destroy(int $id)
    {
        $user = User::query()->where('super_admin', '=', false)->find($id);

        if (!$user) {
            return $this->notFound('User not found.');
        }

        DB::beginTransaction();
        $authUser = auth()->user();

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'roles' => $user->roles()->pluck('name')->toArray(),
            'permissions' => $user->permissions()->pluck('name')->toArray(),
        ];

        $user->roles()->detach();
        $user->permissions()->detach();
        $user->delete();

        $this->auditService->logUserActivity(
            'user_deleted',
            $authUser->id,
            $id,
            $userData,
            null
        );

        DB::commit();

        return $this->success(null, 'User has been deleted.');
    }

    /**
     * @OA\Post(
     *     path="/users/{id}/roles",
     *     summary="Assign roles to a user",
     *     description="Assigns roles to a user by ID",
     *     operationId="assignRolesToUser",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of user to assign roles to",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="roles",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User has been updated."),
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
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not authorized."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found.")
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
     *                     property="roles.0",
     *                     type="array",
     *                     @OA\Items(type="string", example="The selected roles.0 is invalid.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function assignRoles(AssignRolesRequest $request, int $id)
    {
        $user = User::query()->where('super_admin', '=', false)->find($id);

        if (!$user) {
            return $this->notFound('User not found.');
        }

        if (auth()->user()->id === $user->id) {
            return $this->error('Roles cannot be self-assigned.');
        }

        DB::beginTransaction();
        $authUser = auth()->user();

        $originalRoles = $user->roles()->pluck('id')->toArray();
        $roleIds = $request->get('roles', []);

        $user->syncRoles($roleIds);

        $this->auditService->logUserRolesUpdate(
            'user_roles_updated',
            $authUser->id,
            $user->id,
            Role::query()->whereIn('id', $originalRoles)->pluck('name')->toArray(),
            Role::query()->whereIn('id', $roleIds)->pluck('name')->toArray(),
        );

        DB::commit();

        return $this->success(
            UserResource::make($user->load(['roles', 'permissions', 'creator', 'updater'])),
            'User has been updated.'
        );
    }

    /**
     * @OA\Post(
     *     path="/users/{id}/permissions",
     *     summary="Assign permissions to a user",
     *     description="Assigns direct permissions to a user by ID",
     *     operationId="assignPermissionsToUser",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of user to assign permissions to",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User has been updated."),
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
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not authorized."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found.")
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
     *                     property="permissions.0",
     *                     type="array",
     *                     @OA\Items(type="string", example="The selected permissions.0 is invalid.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function assignPermissions(AssignPermissionsRequest $request, int $id)
    {
        $user = User::query()->where('super_admin', '=', false)->find($id);

        if (!$user) {
            return $this->notFound('User not found.');
        }

        if (auth()->user()->id === $user->id) {
            return $this->error('Permissions cannot be self-assigned.');
        }

        DB::beginTransaction();
        $authUser = auth()->user();

        $originalPermissions = $user->permissions()->pluck('id')->toArray();

        $authPermissions = $authUser->getAllPermissions()->pluck('id')->toArray();
        $permissionIds = $request->get('permissions');
        $allowedPermissionIds = array_intersect($permissionIds, $authPermissions);

        if (empty($allowedPermissionIds) && !empty($permissionIds)) {
            $this->auditService->logSecurityViolation(
                'unauthorized_permission_assignment',
                $authUser->id,
                [
                    'attempted_action' => 'assign_permissions',
                    'target_user_id' => $user->id,
                    'requested_permissions' => $permissionIds,
                    'user_permissions' => $authPermissions
                ]
            );

            DB::rollBack();
            return $this->forbidden('Unauthorized permission assignment.');
        }

        $user->syncPermissions($allowedPermissionIds);

        $this->auditService->logUserPermissionsUpdate(
            'user_permissions_updated',
            $authUser->id,
            $user->id,
            Permission::query()->whereIn('id', $originalPermissions)->pluck('name')->toArray(),
            Permission::query()->whereIn('id', $allowedPermissionIds)->pluck('name')->toArray()
        );

        DB::commit();

        return $this->success(
            UserResource::make($user->load(['roles', 'permissions', 'creator', 'updater'])),
            'User has been updated.'
        );
    }
}
