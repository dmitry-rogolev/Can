<?php

namespace dmitryrogolev\Can\Tests\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Запустить сидер
     */
    public function run(): void
    {
        $permissions = config('can.models.permission')::all();
        $userPermissions = $permissions->where('model', 'User');
        $permissionPermissions = $permissions->where('model', 'Permission');

        $user = config('can.models.user')::factory()->create();
        foreach ($permissions as $permission) {
            $user->permissions()->attach($permission);
        }

        $user = config('can.models.user')::factory()->create();
        foreach ($userPermissions as $permission) {
            $user->permissions()->attach($permission);
        }

        $user = config('can.models.user')::factory()->create();
        foreach ($permissionPermissions as $permission) {
            $user->permissions()->attach($permission);
        }
    }
}
