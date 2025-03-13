<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    public function logAuthActivity(string $action, ?int $userId, array $details): AuditLog
    {
        return AuditLog::create([
            'action' => $action,
            'entity_type' => 'authentication',
            'entity_id' => $userId,
            'performed_by' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'additional_data' => json_encode($details),
        ]);
    }

    public function logUserActivity(string $action, int $performedById, ?int $userId, ?array $previousState, ?array $newState): AuditLog
    {
        return AuditLog::create([
            'action' => $action,
            'entity_type' => 'user',
            'entity_id' => $userId,
            'performed_by' => $performedById,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'previous_state' => $previousState ? json_encode($previousState) : null,
            'new_state' => $newState ? json_encode($newState) : null,
        ]);
    }

    public function logUserRolesUpdate(string $action, int $performedById, int $userId, array $previousRoles, array $newRoles): AuditLog
    {
        return AuditLog::create([
            'action' => $action,
            'entity_type' => 'user_roles',
            'entity_id' => $userId,
            'performed_by' => $performedById,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'previous_state' => json_encode(['roles' => $previousRoles]),
            'new_state' => json_encode(['roles' => $newRoles]),
        ]);
    }

    public function logUserPermissionsUpdate(string $action, int $performedById, int $userId, array $previousPermissions, array $newPermissions): AuditLog
    {
        return AuditLog::create([
            'action' => $action,
            'entity_type' => 'user_permissions',
            'entity_id' => $userId,
            'performed_by' => $performedById,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'previous_state' => json_encode(['permissions' => $previousPermissions]),
            'new_state' => json_encode(['permissions' => $newPermissions]),
        ]);
    }

    public function logPermissionRemoval(string $action, int $performedById, int $entityId, int $permissionId, string $entityType): AuditLog
    {
        return AuditLog::create([
            'action' => $action,
            'entity_type' => $entityType === 'role' ? 'role_permission' : 'user_permission',
            'entity_id' => $entityId,
            'performed_by' => $performedById,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'additional_data' => json_encode([
                'permission_id' => $permissionId,
                'entity_id' => $entityId,
                'entity_type' => $entityType
            ]),
        ]);
    }

    public function logRoleActivity(string $action, int $performedById, ?int $roleId, ?array $previousState, ?array $newState): AuditLog
    {
        return AuditLog::create([
            'action' => $action,
            'entity_type' => 'role',
            'entity_id' => $roleId,
            'performed_by' => $performedById,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'previous_state' => $previousState ? json_encode($previousState) : null,
            'new_state' => $newState ? json_encode($newState) : null,
        ]);
    }

    public function logPermissionActivity(string $action, int $performedById, ?int $permissionId, ?array $previousState, ?array $newState): AuditLog
    {
        return AuditLog::create([
            'action' => $action,
            'entity_type' => 'permission',
            'entity_id' => $permissionId,
            'performed_by' => $performedById,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'previous_state' => $previousState ? json_encode($previousState) : null,
            'new_state' => $newState ? json_encode($newState) : null,
        ]);
    }

    public function logSecurityViolation(string $violationType, int $userId, array $details): AuditLog
    {
        return AuditLog::create([
            'action' => 'security_violation',
            'entity_type' => 'security',
            'entity_id' => null,
            'performed_by' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'additional_data' => json_encode([
                'violation_type' => $violationType,
                'details' => $details,
                'timestamp' => now()
            ]),
        ]);
    }
}
