<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends BaseController
{
    protected $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * @OA\Get(
     *     path="/roles",
     *     summary="Get list of roles",
     *     description="Returns a list of all roles with their permissions",
     *     operationId="getRoles",
     *     tags={"Roles"},
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
     *                 @OA\Items(ref="#/components/schemas/Role")
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
        $roles = Role::with('permissions')->get();
        return $this->success(RoleResource::collection($roles));
    }

    /**
     * @OA\Get(
     *     path="/roles/{id}",
     *     summary="Get role details",
     *     description="Returns details for a specific role by ID",
     *     operationId="getRoleById",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of role to return",
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
     *                 ref="#/components/schemas/Role"
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
     *         description="Role not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Role not found")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return $this->notFound('Role not found');
        }

        return $this->success(RoleResource::make($role));
    }

    /**
     * @OA\Post(
     *     path="/roles",
     *     summary="Create a new role",
     *     description="Creates a new role with permissions",
     *     operationId="createRole",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","permissions"},
     *             @OA\Property(property="name", type="string", example="editor"),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1),
     *                 example={1, 2, 3}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Role"
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
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The name has already been taken.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function store(StoreRoleRequest $request)
    {
        DB::beginTransaction();

        $authUser = auth()->user();

        $role = Role::create([
            'name' => $request->name,
            'created_by' => $authUser->id,
        ]);

        $requestedPermissionIds = $request->permissions;
        $requestedPermissions = Permission::whereIn('id', $requestedPermissionIds)->get();

        $role->syncPermissions($requestedPermissions);

        $this->auditService->logRoleActivity(
            'role_created',
            $authUser->id,
            $role->id,
            null,
            [
                'name' => $role->name,
                'permissions' => $requestedPermissions->pluck('name')
            ]
        );

        DB::commit();

        return $this->success(
            RoleResource::make($role->load('permissions')),
            'Role created successfully'
        );
    }

    /**
     * @OA\Post(
     *     path="/roles/{id}",
     *     summary="Update a role",
     *     description="Updates a role and its permissions by ID",
     *     operationId="updateRole",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of role to update",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="updated_editor"),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1),
     *                 example={1, 2, 3, 4}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Role"
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
     *         description="Role not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Role not found")
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
    public function update(UpdateRoleRequest $request, int $id)
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return $this->notFound('Role not found');
        }

        DB::beginTransaction();

        $authUser = auth()->user();
        $originalRoleData = [
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
            'updated_by' => $authUser->id,
        ];

        if ($request->has('name')) {
            $role->name = $request->name;
            $role->save();
        }

        if ($request->has('permissions')) {
            $requestedPermissionIds = $request->permissions;
            $requestedPermissions = Permission::whereIn('id', $requestedPermissionIds)->get();

            $role->syncPermissions($requestedPermissions);
        }

        $this->auditService->logRoleActivity(
            'role_updated',
            $authUser->id,
            $role->id,
            $originalRoleData,
            [
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray()
            ]
        );

        DB::commit();

        return $this->success(
            RoleResource::make($role->load('permissions')),
            'Role updated successfully'
        );
    }

    /**
     * @OA\Delete(
     *     path="/roles/{id}",
     *     summary="Delete a role",
     *     description="Deletes a role by ID",
     *     operationId="deleteRole",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of role to delete",
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
     *             @OA\Property(property="message", type="string", example="Role deleted successfully")
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
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Role deletion failed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Role not found")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->notFound('Role not found');
        }

        DB::beginTransaction();
        $authUser = auth()->user();

        if ($role->is_system_role) {
            DB::rollBack();
            $this->auditService->logSecurityViolation(
                'system_role_deletion_attempt',
                $authUser->id,
                [
                    'attempted_action' => 'system_role_deletion',
                    'role_id' => $role->id,
                    'role_name' => $role->name
                ]
            );

            return $this->forbidden('Role deletion failed');
        }

        $usersWithRole = DB::table('model_has_roles')
            ->where('role_id', $id)
            ->where('model_type', User::class)
            ->pluck('model_id');

        $originalRoleData = [
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
            'users' => $usersWithRole->toArray()
        ];

        foreach ($usersWithRole as $userId) {
            $user = User::find($userId);
            $user?->removeRole($role->name);
        }

        $role->delete();

        $this->auditService->logRoleActivity(
            'role_deleted',
            $authUser->id,
            $id,
            $originalRoleData,
            null
        );

        DB::commit();

        return $this->success(null, 'Role deleted successfully');
    }
}
