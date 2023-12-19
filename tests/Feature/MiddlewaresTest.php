<?php

namespace dmitryrogolev\Can\Tests\Feature;

use dmitryrogolev\Can\Tests\TestCase;

class MiddlewaresTest extends TestCase
{
    /**
     * Проверяем возможность работы с маршрутами
     */
    public function test_welcome(): void
    {
        $response = $this->get('welcome');

        $response->assertStatus(200);
    }

    /**
     * Проверяем аутентицикацию пользователя
     */
    public function test_profile(): void
    {
        $user = config('can.models.user')::factory()->create();

        $response = $this->actingAs($user)->get('profile');

        $response->assertStatus(200);
    }

    /**
     * Проверяем наличие разрешения
     */
    public function test_has_permission(): void
    {
        // Пользователь имеет необходимое разрешение
        $user = config('can.models.user')::factory()->create();
        $user->attachPermission('create.users');
        $response = $this->actingAs($user)->post('users/create', []);
        $response->assertStatus(200);

        // Пользователь имеет необходимое разрешение
        $user = config('can.models.user')::factory()->create();
        $user->attachPermission('create.permissions|delete.permissions');
        $response = $this->actingAs($user)->post('permissions/create/delete', []);
        $response->assertStatus(200);

        // У пользователя нет требуемого разрешения
        $user = config('can.models.user')::factory()->create();
        $response = $this->actingAs($user)->post('permissions/update', []);
        $response->assertStatus(403);
    }
}
