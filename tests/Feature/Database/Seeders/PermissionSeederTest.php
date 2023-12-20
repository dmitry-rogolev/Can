<?php

namespace dmitryrogolev\Can\Tests\Feature\Database\Seeders;

use dmitryrogolev\Can\Facades\Can;
use dmitryrogolev\Can\Tests\RefreshDatabase;
use dmitryrogolev\Can\Tests\TestCase;

/**
 * Тестируем сидер разрешения.
 */
class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Имя класса сидера.
     */
    protected string $permissionSeeder;

    /**
     * Имя slug'а.
     */
    protected string $slugName;

    public function setUp(): void
    {
        parent::setUp();

        $this->permissionSeeder = config('can.seeders.permission');
        $this->slugName = app(config('can.models.permission'))->getSlugName();
    }

    /**
     * Есть ли метод, возвращающий роли?
     */
    public function test_get_permissions(): void
    {
        $permissions = app($this->permissionSeeder)->getPermissions();
        $checkFields = collect($permissions)->every(
            fn ($item) => array_key_exists('name', $item)
            && array_key_exists($this->slugName, $item)
            && array_key_exists('description', $item)
        );

        $this->assertTrue($checkFields);
    }

    /**
     * Создаются ли модели при запуске сидера?
     */
    public function test_run(): void
    {
        app($this->permissionSeeder)->run();

        $count = count(app($this->permissionSeeder)->getPermissions());
        $this->assertCount($count, Can::all());
    }
}
