<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends BaseController
{
    protected $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * @OA\Get(
     *     path="/permissions",
     *     summary="Get list of permissions",
     *     description="Returns a list of all permissions",
     *     operationId="getPermissions",
     *     tags={"Permissions"},
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
     *                 @OA\Items(ref="#/components/schemas/Permission")
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
    public function index(Request $request)
    {
        $permissions = Permission::query()->get();

        return $this->success(PermissionResource::collection($permissions));
    }

    /**
     * @OA\Post(
     *     path="/permissions",
     *     summary="Create a new permission",
     *     description="Creates a new permission with the provided name and group",
     *     operationId="createPermission",
     *     tags={"Permissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","group"},
     *             @OA\Property(property="name", type="string", example="create_posts"),
     *             @OA\Property(property="group", type="string", example="Posts")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission created successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Permission"
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
    public function store(StorePermissionRequest $request)
    {
        DB::beginTransaction();
        $authUser = auth()->user();

        $permission = Permission::create([
            'name' => $request->name,
            'group' => $request->group,
            'created_by' => $authUser->id,
        ]);

        $this->auditService->logPermissionActivity(
            'permission_created',
            $authUser->id,
            $permission->id,
            null,
            [
                'name' => $permission->name,
                'group' => $permission->group,
            ]
        );

        DB::commit();

        return $this->success(
            PermissionResource::make($permission),
            'Permission created successfully.'
        );
    }

    /**
     * @OA\Post(
     *     path="/permissions/{id}",
     *     summary="Update a permission",
     *     description="Updates a permission with the provided name and/or group",
     *     operationId="updatePermission",
     *     tags={"Permissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of permission to update",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="edit_posts"),
     *             @OA\Property(property="group", type="string", example="Posts")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission updated successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Permission"
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
     *         description="Permission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Permission not found.")
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
    public function update(UpdatePermissionRequest $request, int $id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return $this->notFound('Permission not found.');
        }

        DB::beginTransaction();
        $authUser = auth()->user();

        $originalData = [
            'name' => $permission->name,
            'group' => $permission->group,
            'updated_by' => $authUser->id,
        ];

        if ($request->has('name')) {
            $permission->name = $request->name;
        }

        if ($request->has('group')) {
            $permission->group = $request->group;
        }

        $permission->save();

        $this->auditService->logPermissionActivity(
            'permission_updated',
            $authUser->id,
            $permission->id,
            $originalData,
            [
                'name' => $permission->name,
                'group' => $permission->group,
            ]
        );

        DB::commit();

        return $this->success(
            PermissionResource::make($permission),
            'Permission updated successfully.'
        );
    }

    /**
     * @OA\Delete(
     *     path="/permissions/{id}",
     *     summary="Delete a permission",
     *     description="Deletes a permission by ID",
     *     operationId="deletePermission",
     *     tags={"Permissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of permission to delete",
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
     *             @OA\Property(property="message", type="string", example="Permission deleted successfully.")
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
     *             @OA\Property(property="message", type="string", example="Permission deletion failed.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Permission not found.")
     *         )
     *     )
     * )
     */
    public function destroy(int $id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return $this->notFound('Permission not found.');
        }

        if ($permission->is_system_permission) {
            $this->auditService->logSecurityViolation(
                'system_permission_deletion_attempt',
                auth()->id(),
                [
                    'attempted_action' => 'permission_deletion',
                    'permission_id' => $id,
                    'permission_name' => $permission->name
                ]
            );

            return $this->error('Permission deletion failed.');
        }

        DB::beginTransaction();
        $authUser = auth()->user();
        $permissionName = $permission->name;

        $roles = Role::whereHas('permissions', function ($query) use ($id) {
            $query->where('id', $id);
        })->get();

        $usersWithDirectPermission = DB::table('model_has_permissions')
            ->where('permission_id', $id)
            ->where('model_type', User::class)
            ->pluck('model_id');

        $affectedEntities = [
            'roles' => $roles->pluck('name')->toArray(),
            'direct_users' => $usersWithDirectPermission->toArray()
        ];

        foreach ($roles as $role) {
            $this->auditService->logPermissionRemoval(
                'permission_removed_from_role',
                $authUser->id,
                $role->id,
                $id,
                'role'
            );

            $role->revokePermissionTo($permissionName);
        }

        foreach ($usersWithDirectPermission as $userId) {
            $user = User::find($userId);
            if ($user) {
                $this->auditService->logPermissionRemoval(
                    'permission_removed_from_user',
                    $authUser->id,
                    $userId,
                    $id,
                    'user'
                );

                $user->revokePermissionTo($permissionName);
            }
        }

        $permissionData = [
            'id' => $permission->id,
            'name' => $permission->name,
            'group' => $permission->group,
            'affected_entities' => $affectedEntities
        ];

        $permission->delete();

        $this->auditService->logPermissionActivity(
            'permission_deleted',
            $authUser->id,
            $id,
            $permissionData,
            null
        );

        DB::commit();

        return $this->success(null, 'Permission deleted successfully.');
    }
}
