<?php

namespace dmitryrogolev\Can\Tests\Feature\Http\Middlewares;

use dmitryrogolev\Can\Tests\RefreshDatabase;
use dmitryrogolev\Can\Tests\TestCase;

/**
 * Тестируем посредника, проверяющего наличие разрешения у пользователя.
 */
class VerifyPermissionTest extends TestCase
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
     * Имя slug'а.
     */
    protected string $slugName;

    public function setUp(): void
    {
        parent::setUp();

        $this->model = config('can.models.permission');
        $this->user = config('can.models.user');
        $this->slugName = app($this->model)->getSlugName();
    }

    /**
     * Можно ли посетить страницу без аутентификации, но с необходимым разрешением?
     */
    public function test_without_auth(): void
    {
        $response = $this->get('permission/view.users');
        $response->assertStatus(403);
    }

    /**
     * Можно ли посетить страницу с аутентификацией и со случайным разрешением?
     */
    public function test_with_some_permission(): void
    {
        $user = $this->generate($this->user);
        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);

        $response = $this->actingAs($user)->get('permission/edit.users');
        $response->assertStatus(403);
    }

    /**
     * Можно ли посетить страницу с необходимым разрешением?
     */
    public function test_with_permission(): void
    {
        $user = $this->generate($this->user);
        $permission = $this->generate($this->model, [$this->slugName => 'create.users']);
        $user->permissions()->attach($permission);

        $response = $this->actingAs($user)->post('permission/create.users');
        $response->assertStatus(200);
    }

    /**
     * Можно ли посетить страницу, имея одно из требуемых разрешений?
     */
    public function test_with_several_permissions(): void
    {
        $user = $this->generate($this->user);
        $permission = $this->generate($this->model, [$this->slugName => 'edit.users']);
        $user->permissions()->attach($permission);

        $response = $this->actingAs($user)->post('permission/view.users/create.users/edit.users');
        $response->assertStatus(200);
    }
}
