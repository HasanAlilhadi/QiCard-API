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

class UserController extends BaseController
{
    protected $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    // Since this is just a task, I didn’t spend time implementing the filter functionality.
    public function index()
    {
        $query = User::query()->with(['roles', 'permissions', 'creator', 'updater']);

        $users = $query->get();
        return $this->success(UserResource::collection($users));
    }

    public function show(int $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->notFound('User not found.');
        }

        $user = $user->load(['roles', 'permissions', 'creator', 'updater']);

        return $this->success(UserResource::make($user));
    }

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
            $originalRoles,
            $roleIds
        );

        DB::commit();

        return $this->success(
            UserResource::make($user->load(['roles', 'permissions', 'creator', 'updater'])),
            'User has been updated.'
        );
    }

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
            $originalPermissions,
            $allowedPermissionIds
        );

        DB::commit();

        return $this->success(
            UserResource::make($user->load(['roles', 'permissions', 'creator', 'updater'])),
            'User has been updated.'
        );
    }
}
