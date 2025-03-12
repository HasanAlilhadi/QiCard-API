<?php

namespace Database\Seeders;

use App\Models\Car;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
//        User::create([
//            'username' => 'mrlaruso',
//            'name' => 'Hasan Alilhadi',
//            'password' => '1234'
//        ]);

        $role = Role::create([
            'name' => 'welcommer'
        ]);
        $user = User::query()->find(1);
        $user->roles()->attach($role);
    }
}
