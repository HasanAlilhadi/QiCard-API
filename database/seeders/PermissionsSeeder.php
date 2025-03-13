<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Users
            [
                'name' => 'show_users',
                'group' => 'Users',
                'is_system_permission' => true,
            ],
            [
                'name' => 'create_users',
                'group' => 'Users',
                'is_system_permission' => true,
            ],
            [
                'name' => 'edit_users',
                'group' => 'Users',
                'is_system_permission' => true,
            ],
            [
                'name' => 'delete_users',
                'group' => 'Users',
                'is_system_permission' => true,
            ],

            // Permissions
            [
                'name' => 'show_permissions',
                'group' => 'Permissions',
                'is_system_permission' => true,
            ],
            [
                'name' => 'create_permissions',
                'group' => 'Permissions',
                'is_system_permission' => true,
            ],
            [
                'name' => 'edit_permissions',
                'group' => 'Permissions',
                'is_system_permission' => true,
            ],
            [
                'name' => 'delete_permissions',
                'group' => 'Permissions',
                'is_system_permission' => true,
            ],
            [
                'name' => 'assign_permissions',
                'group' => 'Permissions',
                'is_system_permission' => true,
            ],

            // Roles
            [
                'name' => 'show_roles',
                'group' => 'Roles',
                'is_system_permission' => true,
            ],
            [
                'name' => 'create_roles',
                'group' => 'Roles',
                'is_system_permission' => true,
            ],
            [
                'name' => 'edit_roles',
                'group' => 'Roles',
                'is_system_permission' => true,
            ],
            [
                'name' => 'delete_roles',
                'group' => 'Roles',
                'is_system_permission' => true,
            ],
            [
                'name' => 'assign_roles',
                'group' => 'Roles',
                'is_system_permission' => true,
            ],

            // AuditLogs
            [
                'name' => 'show_audit_logs',
                'group' => 'AuditLogs',
                'is_system_permission' => true,
            ]
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate($permissionData);
        }
    }
}
