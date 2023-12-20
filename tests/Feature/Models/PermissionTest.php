<?php

namespace dmitryrogolev\Can\Tests\Feature\Models;

use dmitryrogolev\Can\Contracts\PermissionHasRelations;
use dmitryrogolev\Can\Tests\TestCase;
use dmitryrogolev\Contracts\Sluggable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Тестируем модель роли.
 */
class PermissionTest extends TestCase
{
    /**
     * Имя модели.
     */
    protected string $model;

    /**
     * Имя первичного ключа.
     */
    protected string $keyName;

    public function setUp(): void
    {
        parent::setUp();

        $this->model = config('can.models.permission');
        $this->keyName = config('can.primary_key');
    }

    /**
     * Совпадает ли имя первичного ключа модели с конфигом?
     */
    public function test_primary_key(): void
    {
        $permission = app($this->model);

        $this->assertEquals($this->keyName, $permission->getKeyName());
    }

    /**
     * Совпадает ли флаг включения временных меток в модели с конфигом?
     */
    public function test_timestamps(): void
    {
        $permission = app($this->model);

        $this->assertEquals(config('can.uses.timestamps'), $permission->usesTimestamps());
    }

    /**
     * Совпадает ли имя таблицы модели с конфигом?
     */
    public function test_table(): void
    {
        $permission = app($this->model);

        $this->assertEquals(config('can.tables.permissions'), $permission->getTable());
    }

    /**
     * Реализует ли модель интерфейс отношений разрешения?
     */
    public function test_implements_permission_has_relations(): void
    {
        $permission = app($this->model);

        $this->assertInstanceOf(PermissionHasRelations::class, $permission);
    }

    /**
     * Реализует ли модель интерфейс функционала, облегчающего работу с аттрибутом "slug"?
     */
    public function test_implements_sluggable(): void
    {
        $permission = app($this->model);

        $this->assertInstanceOf(Sluggable::class, $permission);
    }

    /**
     * Совпадает ли фабрика модели с конфигурацией?
     */
    public function test_factory(): void
    {
        $this->assertEquals(config('can.factories.permission'), $this->model::factory()::class);
    }

    /**
     * Подключены ли трейты "\Illuminate\Database\Eloquent\Concerns\HasUuids"
     * и "\Illuminate\Database\Eloquent\SoftDeletes" согласно конфигурации?
     */
    public function test_uses_traits(): void
    {
        $permission = app($this->model);
        $traits = collect(class_uses_recursive($permission));
        $hasUuids = $traits->contains(HasUuids::class);
        $softDeletes = $traits->contains(SoftDeletes::class);

        if (config('can.uses.uuid') && config('can.uses.soft_deletes')) {
            $this->assertTrue($hasUuids);
            $this->assertTrue($softDeletes);
        } elseif (config('can.uses.uuid')) {
            $this->assertTrue($hasUuids);
            $this->assertFalse($softDeletes);
        } elseif (config('can.uses.soft_deletes')) {
            $this->assertFalse($hasUuids);
            $this->assertTrue($softDeletes);
        } else {
            $this->assertFalse($hasUuids);
            $this->assertFalse($softDeletes);
        }
    }
}
