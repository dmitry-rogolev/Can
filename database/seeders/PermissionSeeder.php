<?php

namespace dmitryrogolev\Can\Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Запустить сидер
     */
    public function run(): void
    {
        $permissions = [
            [
                'name'        => 'Can View Users',
                'slug'        => 'view.users',
                'description' => 'Can view users',
                'model'       => 'User', 
            ],
            [
                'name'        => 'Can Create Users',
                'slug'        => 'create.users',
                'description' => 'Can create new users',
                'model'       => 'User', 
            ],
            [
                'name'        => 'Can Edit Users',
                'slug'        => 'edit.users',
                'description' => 'Can edit users',
                'model'       => 'User', 
            ],
            [
                'name'        => 'Can Delete Users',
                'slug'        => 'delete.users',
                'description' => 'Can delete users',
                'model'       => 'User', 
            ],
            [
                'name'        => 'Can Restore Users',
                'slug'        => 'restore.users',
                'description' => 'Can restore users',
                'model'       => 'User', 
            ],
            [
                'name'        => 'Can Destroy Users',
                'slug'        => 'destroy.users',
                'description' => 'Can destroy users',
                'model'       => 'User', 
            ],

            [
                'name'        => 'Can View Permissions',
                'slug'        => 'view.permissions',
                'description' => 'Can view permissions',
                'model'       => 'Permission', 
            ],
            [
                'name'        => 'Can Create Permissions',
                'slug'        => 'create.permissions',
                'description' => 'Can create new permissions',
                'model'       => 'Permission', 
            ],
            [
                'name'        => 'Can Edit Permissions',
                'slug'        => 'edit.permissions',
                'description' => 'Can edit permissions',
                'model'       => 'Permission', 
            ],
            [
                'name'        => 'Can Delete Permissions',
                'slug'        => 'delete.permissions',
                'description' => 'Can delete permissions',
                'model'       => 'Permission', 
            ],
            [
                'name'        => 'Can Restore Permissions',
                'slug'        => 'restore.permissions',
                'description' => 'Can restore permissions',
                'model'       => 'Permission', 
            ],
            [
                'name'        => 'Can Destroy Permissions',
                'slug'        => 'destroy.permissions',
                'description' => 'Can destroy permissions',
                'model'       => 'Permission', 
            ],
        ];

        foreach ($permissions as $permission) {
            if (! config('can.models.permission')::whereSlug($permission['slug'])->first()) {
                config('can.models.permission')::create($permission);
            }
        }
    }
}
