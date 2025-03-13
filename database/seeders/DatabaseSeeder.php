<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            RoleSeeder::class,
        ]);

        $user = User::create([
            'username' => 'admin',
            'name' => 'Hasan Alilhadi',
            'password' => '1234',
            'super_admin' => true
        ]);

        Permission::query()->update([
            'created_by' => $user->id
        ]);

        $permissions = Permission::query()->get()->pluck('id')->toArray();
        $user->permissions()->sync($permissions);
        $user->assignRole('super_admin');

        DB::table('personal_access_tokens')->truncate();
    }
}
