<?php

namespace dmitryrogolev\Can\Tests\Feature;

use dmitryrogolev\Can\Tests\TestCase;
use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionMethod;

/**
 * Тестируем параметры конфигурации.
 */
class ConfigTest extends TestCase
{
    /**
     * Совпадает ли количество тестов с количеством переменных конфигурации?
     */
    public function test_count(): void
    {
        $FUNCTION = __FUNCTION__;
        $count = count(Arr::flatten(config('can')));
        $methods = (new ReflectionClass($this))->getMethods(ReflectionMethod::IS_PUBLIC);
        $methods = array_values(array_filter($methods,
            fn ($method) => str_starts_with($method->name, 'test_') && $method->name !== $FUNCTION
        ));

        $this->assertCount($count, $methods);
    }

    /**
     * Есть ли конфигурация имени таблицы разрешений?
     */
    public function test_tables_permissions(): void
    {
        $this->assertTrue(is_string(config('can.tables.permissions')));
        $this->assertNotEmpty(config('can.tables.permissions'));
    }

    /**
     * Есть ли конфигурация имени промежуточной таблицы разрешений?
     */
    public function test_tables_permissionables(): void
    {
        $this->assertTrue(is_string(config('can.tables.permissionables')));
        $this->assertNotEmpty(config('can.tables.permissionables'));
    }

    /**
     * Есть ли конфигурация имени полиморфной связи промежуточной таблицы?
     */
    public function test_relations_permissionable(): void
    {
        $this->assertTrue(is_string(config('can.relations.permissionable')));
        $this->assertNotEmpty(config('can.relations.permissionable'));
    }

    /**
     * Есть ли конфигурация имени первичного ключа?
     */
    public function test_primary_key(): void
    {
        $this->assertTrue(is_string(config('can.primary_key')));
        $this->assertNotEmpty(config('can.primary_key'));
    }

    /**
     * Есть ли конфигурация разделителя строк?
     */
    public function test_separator(): void
    {
        $this->assertTrue(is_string(config('can.separator')));
        $this->assertNotEmpty(config('can.separator'));
    }

    /**
     * Есть ли конфигурация имени модели разрешений?
     */
    public function test_models_permission(): void
    {
        $this->assertTrue(class_exists(config('can.models.permission')));
    }

    /**
     * Есть ли конфигурация имени модели промежуточной таблицы?
     */
    public function test_models_permissionable(): void
    {
        $this->assertTrue(class_exists(config('can.models.permissionable')));
    }

    /**
     * Есть ли конфигурация имени модели пользователя?
     */
    public function test_models_user(): void
    {
        $this->assertTrue(class_exists(config('can.models.user')));
    }

    /**
     * Есть ли конфигурация имени фабрики модели разрешений?
     */
    public function test_factories_permission(): void
    {
        $this->assertTrue(class_exists(config('can.factories.permission')));
    }

    /**
     * Есть ли конфигурация имени сидера модели разрешений?
     */
    public function test_seeders_permission(): void
    {
        $this->assertTrue(class_exists(config('can.seeders.permission')));
    }

    /**
     * Есть ли конфигурация флага использования UUID?
     */
    public function test_uses_uuid(): void
    {
        $this->assertTrue(is_bool(config('can.uses.uuid')));
    }

    /**
     * Есть ли конфигурация флага программного удаления моделей?
     */
    public function test_uses_soft_deletes(): void
    {
        $this->assertTrue(is_bool(config('can.uses.soft_deletes')));
    }

    /**
     * Есть ли конфигурация флага временных меток моделей?
     */
    public function test_uses_timestamps(): void
    {
        $this->assertTrue(is_bool(config('can.uses.timestamps')));
    }

    /**
     * Есть ли конфигурация флага регистрации миграций?
     */
    public function test_uses_migrations(): void
    {
        $this->assertTrue(is_bool(config('can.uses.migrations')));
    }

    /**
     * Есть ли конфигурация флага регистрации сидеров?
     */
    public function test_uses_seeders(): void
    {
        $this->assertTrue(is_bool(config('can.uses.seeders')));
    }

    /**
     * Есть ли конфигурация флага регистрации директив blade'а?
     */
    public function test_uses_blade(): void
    {
        $this->assertTrue(is_bool(config('can.uses.blade')));
    }

    /**
     * Есть ли конфигурация флага регистрации посредников?
     */
    public function test_uses_middlewares(): void
    {
        $this->assertTrue(is_bool(config('can.uses.middlewares')));
    }

    /**
     * Есть ли конфигурация флага подгрузки отношений после обновления?
     */
    public function test_uses_load_on_update(): void
    {
        $this->assertTrue(is_bool(config('can.uses.load_on_update')));
    }

    /**
     * Есть ли конфигурация флага расширения метода "can"?
     */
    public function test_uses_extend_can_method(): void
    {
        $this->assertTrue(is_bool(config('can.uses.extend_can_method')));
    }
}
