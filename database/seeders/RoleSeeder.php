<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::create([
            'name' => 'super_admin',
            'is_system_role' => true
        ]);

        $permissions = Permission::all()->pluck('id')->toArray();
        $role->syncPermissions($permissions);
    }
}
