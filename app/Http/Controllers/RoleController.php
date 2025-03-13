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

    // Since this is just a task, I didn’t spend time implementing the filter functionality.
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return $this->success(RoleResource::collection($roles));
    }

    public function show(int $id)
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return $this->notFound('Role not found');
        }

        return $this->success(RoleResource::make($role));
    }

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

    public function update(UpdateRoleRequest $request, int $id)
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return $this->notFound('Role not found');
        }

        if ($role->is_system_role) {
            $this->auditService->logSecurityViolation(
                'system_role_modification_attempt',
                auth()->id(),
                [
                    'attempted_action' => 'system_role_update',
                    'role_id' => $id,
                    'role_name' => $role->name
                ]
            );

            return $this->error('System role cannot be modified.');
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
