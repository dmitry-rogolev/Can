<?php

namespace dmitryrogolev\Can\Tests\Feature\Traits;

use dmitryrogolev\Can\Tests\RefreshDatabase;
use dmitryrogolev\Can\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Тестируем функционал, добавляющий модели разрешения отношения с другими моделями.
 */
class PermissionHasRelationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Имя модели.
     */
    protected string $model;

    /**
     * Имя модели пользователя.
     */
    protected string $user;

    /**
     * Имя промежуточной модели.
     */
    protected string $pivot;

    /**
     * Имя первичного ключа.
     */
    protected string $keyName;

    public function setUp(): void
    {
        parent::setUp();

        $this->model = config('can.models.permission');
        $this->user = config('can.models.user');
        $this->pivot = config('can.models.permissionable');
        $this->keyName = config('can.primary_key');
    }

    /**
     * Относится ли разрешение к множеству моделей?
     */
    public function test_permissionables(): void
    {
        $permission = $this->generate($this->model);

        $users = $this->generate($this->user, 3);
        $users->each(fn ($user) => $user->permissions()->attach($permission));
        $expected = $users->pluck($this->keyName)->all();
        $relation = $permission->permissionables($this->user);
        $actual = $relation->get()->pluck($this->keyName)->all();

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                         Подтверждаем возврат отношения.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $this->assertInstanceOf(MorphToMany::class, $relation);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Подтверждаем получение отношения.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $this->assertEquals($expected, $actual);
    }

    /**
     * Есть ли временные метки у загруженных отношений?
     */
    public function test_permissionables_with_timestamps(): void
    {
        $permission = $this->generate($this->model);
        $users = $this->generate($this->user, 3);
        $users->each(fn ($user) => $user->permissions()->attach($permission));
        $createdAtColumn = app($this->pivot)->getCreatedAtColumn();
        $updatedAtColumn = app($this->pivot)->getUpdatedAtColumn();

        // ! ||--------------------------------------------------------------------------------||
        // ! ||            Подтверждаем наличие временных меток при включении опции.           ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.timestamps' => true]);
        $pivot = $permission->permissionables($this->user)->first()->pivot;
        $this->assertNotNull($pivot->{$createdAtColumn});
        $this->assertNotNull($pivot->{$updatedAtColumn});

        // ! ||--------------------------------------------------------------------------------||
        // ! ||          Подтверждаем отсутствие временных меток при отключении опции.         ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.timestamps' => false]);
        $pivot = $permission->permissionables($this->user)->first()->pivot;
        $this->assertNull($pivot->{$createdAtColumn});
        $this->assertNull($pivot->{$updatedAtColumn});
    }
}
