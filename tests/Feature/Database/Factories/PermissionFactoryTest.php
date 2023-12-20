<?php

namespace dmitryrogolev\Can\Tests\Feature\Database\Factories;

use dmitryrogolev\Can\Tests\RefreshDatabase;
use dmitryrogolev\Can\Tests\TestCase;

/**
 * Тестируем фабрику разрешения.
 */
class PermissionFactoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Имя фабрики разрешений.
     */
    protected string $factory;

    /**
     * Имя slug'а.
     *
     * @var string
     */
    protected string $slugName;

    public function setUp(): void
    {
        parent::setUp();

        $this->factory = config('can.factories.permission');
        $this->slugName = app(config('can.models.permission'))->getSlugName();
    }

    /**
     * Есть ли метод, возвращающий поля модели согласно конфигурации?
     */
    public function test_definition(): void
    {
        $state = app($this->factory)->definition();

        $hasFields = array_key_exists('name', $state)
            && array_key_exists($this->slugName, $state)
            && array_key_exists('description', $state);

        $this->assertTrue($hasFields);
    }

    /**
     * Создает ли фабрика модель?
     */
    public function test_created(): void
    {
        $permission = app($this->factory)->create();
        $this->assertModelExists($permission);
    }
}
