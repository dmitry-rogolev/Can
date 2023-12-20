<?php

namespace dmitryrogolev\Can\Tests\Feature\Services;

use dmitryrogolev\Can\Facades\Can;
use dmitryrogolev\Can\Services\PermissionService;
use dmitryrogolev\Can\Tests\RefreshDatabase;
use dmitryrogolev\Can\Tests\TestCase;
use dmitryrogolev\Contracts\Resourcable;

/**
 * Тестируем сервис работы с таблицей ролей.
 */
class ServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Имя первичного ключа.
     */
    protected string $keyName;

    /**
     * Имя модели.
     */
    protected string $model;

    /**
     * Имя модели пользователя.
     */
    protected string $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->keyName = config('can.primary_key');
        $this->model = config('can.models.permission');
        $this->user = config('can.models.user');
    }

    /**
     * Настроен ли сервис?
     */
    public function test_configuration(): void
    {
        // ! ||--------------------------------------------------------------------------------||
        // ! ||            Подтверждаем установку модели, с которой работает сервис.           ||
        // ! ||--------------------------------------------------------------------------------||

        $expected = $this->model;
        $actual = Can::getModel();
        $this->assertEquals($expected, $actual);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                      Подтверждаем установку сидера модели.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $expected = config('can.seeders.permission');
        $actual = Can::getSeeder();
        $this->assertEquals($expected, $actual);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                 Подтверждаем реализацию ресурсного интерфейса.                 ||
        // ! ||--------------------------------------------------------------------------------||

        $service = new PermissionService;
        $this->assertInstanceOf(Resourcable::class, $service);
    }
}
