<?php

namespace dmitryrogolev\Can\Tests\Feature;

use dmitryrogolev\Can\Tests\TestCase;

class PermissionTest extends TestCase
{
    /**
     * Проверяем, что имя таблицы разрешений совпадает с именем таблицы из конфига.
     *
     * @return void
     */
    public function test_table(): void 
    {
        $this->assertEquals(config('can.tables.permissions'), (new (config('can.models.permission')))->getTable());
    }

    /**
     * Проверяем, что имя первичного ключа разрешения совпадает с первичным ключом из конфига.
     *
     * @return void
     */
    public function test_primary_key(): void 
    {
        $this->assertEquals(config('can.primary_key'), (new (config('can.models.permission')))->getKeyName());
    }

    /**
     * Проверяем статус временных меток
     *
     * @return void
     */
    public function test_timestamps(): void 
    {
        $this->assertEquals(config('can.uses.timestamps'), (new (config('can.models.permission')))->usesTimestamps());
    }

    /**
     * Получаем разрешение по ее slug
     *
     * @return void
     */
    public function test_get_a_permission_by_slug(): void
    {
        $this->assertModelExists(config('can.models.permission')::canCreateUsers());
        $this->assertModelExists(config('can.models.permission')::canDeletePermissions());
        $this->assertModelExists(config('can.models.permission')::canViewUsers());

        $this->expectException(\BadMethodCallException::class);
        config('can.models.permission')::undefined();
    }

    /**
     * Проверяем наличие фабрики
     *
     * @return void
     */
    public function test_factory(): void 
    {
        $this->assertEquals(config('can.factories.permission'), config('can.models.permission')::factory()::class);
        $this->assertModelExists(config('can.models.permission')::factory()->create());
    }

    /**
     * Проверяем полиморфную связь многие-ко-многим
     *
     * @return void
     */
    public function test_permissionables(): void 
    {
        $canCreateUsers = config('can.models.permission')::canCreateUsers();
        
        $this->assertTrue($canCreateUsers->permissionables(config('can.models.user'))->get()->isNotEmpty());
        $this->assertTrue($canCreateUsers->users->isNotEmpty());
    }
}
