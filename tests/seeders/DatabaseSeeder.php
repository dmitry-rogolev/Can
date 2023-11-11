<?php

namespace dmitryrogolev\Can\Tests\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Запустить сидер
     */
    public function run(): void
    {
        $this->call([
            \dmitryrogolev\Can\Database\Seeders\PermissionSeeder::class, 
            \dmitryrogolev\Can\Tests\Seeders\UserSeeder::class, 
        ]);
    }
}
