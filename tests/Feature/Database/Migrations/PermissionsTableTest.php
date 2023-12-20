<?php

namespace dmitryrogolev\Can\Tests\Feature\Database\Migrations;

use dmitryrogolev\Can\Tests\TestCase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Тестируем миграцию таблицы разрешений.
 */
class PermissionsTableTest extends TestCase
{
    /**
     * Класс миграции.
     */
    protected Migration $migration;

    /**
     * Имя таблицы.
     */
    protected string $table;

    /**
     * Имя модели.
     */
    protected string $model;

    /**
     * Имя первичного ключа.
     */
    protected string $keyName;

    /**
     * Имя временной метки создания записи.
     */
    protected string $createdAt;

    /**
     * Имя временной метки обновления записи.
     */
    protected string $updatedAt;

    /**
     * Имя временной метки удаления записи.
     */
    protected string $deletedAt;

    public function setUp(): void
    {
        parent::setUp();

        $this->migration = require __DIR__.'/../../../../database/migrations/create_permissions_table.php';
        $this->table = config('can.tables.permissions');
        $this->model = config('can.models.permission');
        $this->keyName = config('can.primary_key');
        $this->createdAt = app($this->model)->getCreatedAtColumn();
        $this->updatedAt = app($this->model)->getUpdatedAtColumn();
        $this->deletedAt = app($this->model)->getDeletedAtColumn();
    }

    /**
     * Запускается и откатывается ли миграция?
     */
    public function test_up_down(): void
    {
        $checkTable = fn () => Schema::hasTable($this->table);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                         Подтверждаем создание таблицы.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $this->migration->up();
        $this->assertTrue($checkTable());

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                         Подтверждаем удаление таблицы.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $this->migration->down();
        $this->assertFalse($checkTable());
    }

    /**
     * Есть ли первичный ключ у таблицы?
     */
    public function test_has_id(): void
    {
        $this->migration->up();
        $hasPrimaryKey = Schema::hasColumn($this->table, $this->keyName);

        $this->assertTrue($hasPrimaryKey);
    }

    /**
     * Есть ли временные метки у таблицы?
     */
    public function test_has_timestamps(): void
    {
        $hasCreatedAt = fn () => Schema::hasColumn($this->table, $this->createdAt);
        $hasUpdatedAt = fn () => Schema::hasColumn($this->table, $this->updatedAt);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                      Подтверждаем наличие временных меток.                     ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.timestamps' => true]);
        $this->migration->up();
        $this->assertTrue($hasCreatedAt() && $hasUpdatedAt());
        $this->migration->down();

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                    Подтверждаем отсутствие временных меток.                    ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.timestamps' => false]);
        $this->migration->up();
        $this->assertTrue(! $hasCreatedAt() && ! $hasUpdatedAt());
    }

    /**
     * Есть ли у таблицы поле программного удаления?
     */
    public function test_has_deleted_at(): void
    {
        $checkDeletedAt = fn () => Schema::hasColumn($this->table, $this->deletedAt);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||           Подтверждаем наличие временно метки программного удаления.           ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.soft_deletes' => true]);
        $this->migration->up();
        $this->assertTrue($checkDeletedAt());
        $this->migration->down();

        // ! ||--------------------------------------------------------------------------------||
        // ! ||         Подтверждаем отсутствие временной метки программного удаления.         ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.soft_deletes' => false]);
        $this->migration->up();
        $this->assertTrue(! $checkDeletedAt());
    }
}
