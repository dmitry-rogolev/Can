<?php

namespace dmitryrogolev\Can\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase as TestingRefreshDatabase;

trait RefreshDatabase
{
    use TestingRefreshDatabase; 

    /**
     * Главный сидер, который запускает другие сидеры
     *
     * @var string
     */
    protected string $seeder = \dmitryrogolev\Can\Tests\Seeders\DatabaseSeeder::class;

    /**
     * Следует ли запускать сидеры после миграции
     *
     * @var boolean
     */
    protected bool $seed = true;

    /**
     * Определите миграцию базы данных
     *
     * @return void
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(
            __DIR__.'/migrations'
        );
    }
}