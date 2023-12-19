<?php

namespace dmitryrogolev\Can\Tests\Feature;

use dmitryrogolev\Can\Tests\TestCase;
use Illuminate\Support\Facades\Gate;

class HasPermissionsTest extends TestCase
{
    /**
     * Проверяем получение разрешений
     */
    public function test_get_permissions(): void
    {
        $user = config('can.models.permission')::canCreateUsers()->users()->first();

        $this->assertTrue($user->permissions->isNotEmpty());
    }

    /**
     * Присоединяем разрешение к пользователю
     */
    public function test_attach_permission(): void
    {
        $user = config('can.models.user')::factory()->create();
        $permissions = config('can.models.permission')::all();

        // Присоединяем по идентификатору
        $permission = $permissions->get(2)->getKey();
        $this->assertTrue($user->attachPermission($permission));
        if (! config('can.uses.load_on_update')) {
            $user->loadPermissions();
        }
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->getKey() == $permission));

        // Присоединяем по slug
        $permission = $permissions->get(1)->slug;
        $this->assertTrue($user->attachPermission($permission));
        if (! config('can.uses.load_on_update')) {
            $user->loadPermissions();
        }
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->slug == $permission));

        // Присоединяем по модели
        $permission = $permissions->get(0);
        $this->assertTrue($user->attachPermission($permission));
        if (! config('can.uses.load_on_update')) {
            $user->loadPermissions();
        }
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->is($permission)));

        $permission = 'undefined';
        $this->assertFalse($user->attachPermission($permission));

        $user = config('can.models.user')::factory()->create();

        // Присоединяем по множеству идентификаторов
        $permissions = config('can.models.permission')::all()->map(fn ($item) => $item->getKey());
        $this->assertTrue($user->attachPermission($permissions));
        foreach ($permissions as $permission) {
            $this->assertTrue($user->permissions->contains(fn ($item) => $item->getKey() == $permission));
        }
    }

    /**
     * Отсоединяем разрешение
     */
    public function test_detach_permission(): void
    {
        $user = config('can.models.user')::factory()->create();
        $permissions = config('can.models.permission')::all();
        $this->assertTrue($user->attachPermission($permissions));
        $this->assertTrue($permissions->isNotEmpty());

        // Отсоединяем по идентификатору
        $permission = $permissions->get(0)->getKey();
        $user->detachPermission($permission);
        $this->assertFalse($user->permissions->contains(fn ($item) => $item->getKey() === $permission));

        // Отсоединяем по slug
        $permission = $permissions->get(1)->slug;
        $user->detachPermission($permission);
        $this->assertFalse($user->permissions->contains(fn ($item) => $item->slug === $permission));

        // Отсоединяем по модели
        $permission = $permissions->get(2);
        $user->detachPermission($permission);
        $this->assertFalse($user->permissions->contains(fn ($item) => $item->is($permission)));

        $user = config('can.models.user')::factory()->create();
        for ($i = 0; $i < 10; $i++) {
            $user->permissions()->attach(config('can.models.permission')::factory()->create());
        }
        $user->loadPermissions();
        $this->assertTrue($user->permissions->count() >= 10);

        // Отсоединяем множество ролей по идентификатору
        $permissions = $user->permissions;
        $this->assertTrue($user->detachPermission([$permissions->get(0)->getKey(), $permissions->get(1)->getKey()]));
        $this->assertFalse($user->permissions->contains(fn ($item) => $item->getKey() === $permissions->get(0)->getKey() || $item->getKey() === $permissions->get(1)->getKey()));
    }

    /**
     * Отсоединяем все разрешения
     */
    public function test_detach_all_permissions(): void
    {
        $user = config('can.models.user')::factory()->create();
        $permissions = config('can.models.permission')::all();
        $this->assertTrue($user->attachPermission($permissions));
        $this->assertTrue($user->detachAllPermissions());
        if (! config('can.uses.load_on_update')) {
            $user->loadPermissions();
        }
        $this->assertTrue($user->permissions->isEmpty());
    }

    /**
     * Синхронизируем разрешения
     */
    public function test_sync_permissions(): void
    {
        $user = config('can.models.user')::factory()->create();
        $permissions = config('can.models.permission')::all();
        $this->assertTrue($user->attachPermission($permissions));

        $permissions = config('can.models.permission')::limit(2)->get();
        $user->syncPermissions($permissions);
        if (! config('can.uses.load_on_update')) {
            $user->loadPermissions();
        }
        $this->assertCount($permissions->count(), $user->permissions);
        foreach ($permissions as $permission) {
            $this->assertTrue($user->permissions->contains(fn ($item) => $item->is($permission)));
        }
    }

    /**
     * Проверяем наличие хотябы одного разрешения
     */
    public function test_has_one_permission(): void
    {
        $user = config('can.models.user')::factory()->create();
        $permissions = config('can.models.permission')::all();
        $this->assertTrue($user->attachPermission($permissions));

        $this->assertTrue($user->hasOnePermission($permissions->get(0)->getKey()));
        $this->assertTrue($user->hasOnePermission($permissions->get(1)->slug));
        $this->assertTrue($user->hasOnePermission($permissions));
        $this->assertTrue($user->hasOnePermission([$permissions->get(0)->getKey(), $permissions->get(1)->getKey()]));
        $this->assertTrue($user->hasOnePermission($permissions->get(0)->slug.'|'.$permissions->get(1)->slug));
    }

    /**
     * Проверяем наличие всех разрешений
     */
    public function test_has_all_permissions(): void
    {
        $user = config('can.models.user')::factory()->create();
        $permissions = config('can.models.permission')::all();
        $this->assertTrue($user->attachPermission($permissions));

        $this->assertTrue($user->hasAllPermissions($permissions));
        $this->assertTrue($user->hasAllPermissions($permissions->get(0)->getKey()));
        $this->assertTrue($user->hasAllPermissions($permissions->get(0)->slug));
    }

    /**
     * Проверяем наличие разрешения
     */
    public function test_has_permission(): void
    {
        $user = config('can.models.user')::factory()->create();
        $permissions = config('can.models.permission')::all();
        $this->assertTrue($user->attachPermission($permissions));

        $this->assertTrue($user->hasPermission($permissions->first()));
        $this->assertTrue($user->hasPermission($permissions, true));
    }

    /**
     * Проверяем возможность получения разрешения с помощью магического метода
     */
    public function test_magic_can(): void
    {
        $user = config('can.models.user')::factory()->create();
        $permissions = config('can.models.permission')::all();
        $this->assertTrue($user->attachPermission($permissions));

        $this->assertTrue($user->canCreateUsers());
        $this->assertTrue($user->canDeletePermissions());
        $this->assertTrue($user->canEditUsers());
        $this->assertFalse($user->canUndefined());

        $this->expectException(\BadMethodCallException::class);
        $this->assertFalse($user->undefined());
    }

    /**
     * Тестируем расширение метода can
     */
    public function test_can(): void
    {
        if (! config('can.uses.extend_can_method')) {
            $this->markTestSkipped('Метод can не расширен.');
        }

        $user = config('can.models.user')::factory()->create();
        $permissions = config('can.models.permission')::all();
        $this->assertTrue($user->attachPermission($permissions));

        Gate::define('view-profile', function () {
            return true;
        });

        $this->assertTrue($user->can('create.users'));
        $this->assertTrue($user->can('view-profile'));
        $this->assertFalse($user->can('view-admin'));
    }
}
