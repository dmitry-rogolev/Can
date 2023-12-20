<?php

namespace dmitryrogolev\Can\Tests\Feature\Models;

use dmitryrogolev\Can\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * Тестируем модель промежуточной таблицы разрешений.
 */
class PermissionableTest extends TestCase
{
    /**
     * Имя модели.
     */
    protected string $model;

    public function setUp(): void
    {
        parent::setUp();

        $this->model = config('can.models.permissionable');
    }

    /**
     * Расширяет ли модель класс "\Illuminate\Database\Eloquent\Relations\MorphPivot"?
     */
    public function test_extends_morph_pivot(): void
    {
        $permissionable = app($this->model);

        $this->assertInstanceOf(MorphPivot::class, $permissionable);
    }

    /**
     * Совпадает ли имя таблицы модели с конфигом?
     */
    public function test_table(): void
    {
        $permissionable = app($this->model);

        $this->assertEquals(config('can.tables.permissionables'), $permissionable->getTable());
    }

    /**
     * Совпадает ли флаг включения временных меток в модели с конфигом?
     */
    public function test_timestamps(): void
    {
        $permissionable = app($this->model);

        $this->assertEquals(config('can.uses.timestamps'), $permissionable->usesTimestamps());
    }
}
