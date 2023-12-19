<?php

namespace dmitryrogolev\Can\Tests\Feature;

use dmitryrogolev\Can\Services\PermissionService;
use dmitryrogolev\Can\Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    protected PermissionService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new PermissionService();
    }

    /**
     * Проверяем получение всех разрешений
     */
    public function test_index(): void
    {
        $this->assertCount(config('can.models.permission')::all()->count(), $this->service->index());
    }

    /**
     * Проверяем получение разрешения
     */
    public function test_show(): void
    {
        $this->assertTrue(config('can.models.permission')::canViewUsers()->is($this->service->show('view.users')));
    }

    /**
     * Проверяем возможность создания разрешения
     */
    public function test_store(): void
    {
        $permission = $this->service->store([
            'name' => 'Permission',
            'slug' => 'permission',
        ]);
        $this->assertModelExists($permission);

        $permission = $this->service->store();
        $this->assertModelExists($permission);
    }

    /**
     * Проверяем возможность обновления разрешения
     */
    public function test_update(): void
    {
        $permission = $this->service->store([
            'name' => 'Permission',
            'slug' => 'permission',
            'model' => 'Permission',
        ]);
        $this->service->update($permission, [
            'model' => 'User',
        ]);
        $this->assertNotEquals('Permission', $permission->model);
    }

    /**
     * Проверяем возможность удаления
     */
    public function test_delete(): void
    {
        $permission = $this->service->store();
        $permission->delete();
        if (config('can.uses.soft_deletes')) {
            $this->assertModelExists($permission);
            $this->assertTrue($permission->trashed());
        } else {
            $this->assertModelMissing($permission);
        }
    }

    /**
     * Проверяем возможность удаления
     */
    public function test_force_delete(): void
    {
        $permission = $this->service->store();
        $permission->forceDelete();
        $this->assertModelMissing($permission);
    }

    /**
     * Проверяем возможность восстановления
     */
    public function test_restore(): void
    {
        if (! config('can.uses.soft_deletes')) {
            $this->markTestSkipped('Программное удаление отключено.');
        }

        $permission = $this->service->store();
        $permission->delete();
        $this->assertTrue($permission->trashed());
        $permission->restore();
        $this->assertFalse($permission->trashed());
    }

    /**
     * Проверяем возможность очищения таблицы.
     */
    public function test_trancate(): void
    {
        config('can.models.permission')::factory()->count(10)->create();
        $this->service->truncate();
        $this->assertCount(0, $this->service->index());
    }
}
