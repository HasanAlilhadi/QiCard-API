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

    // Since this is just a task, I didn’t spend time implementing the filter functionality.
    public function index(Request $request)
    {
        $permissions = Permission::query()->get();

        return $this->success(PermissionResource::collection($permissions));
    }

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
