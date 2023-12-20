<?php

namespace Database\Seeders;

use dmitryrogolev\Can\Facades\Can;
use Illuminate\Database\Seeder;

/**
 * Сидер модели разрешений.
 */
class PermissionSeeder extends Seeder
{
    /**
     * Запустить сидер.
     */
    public function run(): void
    {
        Can::createGroupIfNotExists($this->getPermissions());
    }

    /**
     * Возвращает разрешения.
     */
    public function getPermissions(): array
    {
        return [
            ['name' => 'Can View Users', 'slug' => 'view.users', 'description' => 'Can view users'],
            ['name' => 'Can Create Users', 'slug' => 'create.users', 'description' => 'Can create users'],
            ['name' => 'Can Edit Users', 'slug' => 'edit.users', 'description' => 'Can edit users'],
            ['name' => 'Can Delete Users', 'slug' => 'delete.users', 'description' => 'Can delete users'],
            ['name' => 'Can Restore Users', 'slug' => 'restore.users', 'description' => 'Can restore users'],
            ['name' => 'Can Destroy Users', 'slug' => 'destroy.users', 'description' => 'Can destroy users'],
        ];
    }
}
