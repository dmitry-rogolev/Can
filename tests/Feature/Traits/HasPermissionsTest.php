<?php

namespace dmitryrogolev\Can\Tests\Feature\Traits;

use BadMethodCallException;
use dmitryrogolev\Can\Tests\RefreshDatabase;
use dmitryrogolev\Can\Tests\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Gate;

/**
 * Тестируем функционал разрешений.
 */
class HasPermissionsTest extends TestCase
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
     * Имя первичного ключа.
     */
    protected string $keyName;

    /**
     * Имя slug'а.
     */
    protected string $slugName;

    public function setUp(): void
    {
        parent::setUp();

        $this->model = config('can.models.permission');
        $this->user = config('can.models.user');
        $this->keyName = config('can.primary_key');
        $this->slugName = app(config('can.models.permission'))->getSlugName();
    }

    /**
     * Есть ли метод, возвращающий отношение модели с разрешениями?
     */
    public function test_permissions(): void
    {
        $user = $this->generate($this->user);
        $permissions = $this->generate($this->model, 3);
        $permissions->each(fn ($permission) => $user->permissions()->attach($permission));
        $this->generate($this->model, 2);
        $expected = $permissions->pluck($this->keyName)->all();

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                         Подтверждаем возврат отношения.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $query = $user->permissions();
        $this->assertInstanceOf(MorphToMany::class, $query);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                    Подтверждаем получение разрешений модели.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $actual = $user->permissions()->get()->pluck($this->keyName)->all();
        $this->assertEquals($expected, $actual);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||          Подтверждаем наличие временных меток у промежуточных моделей.         ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.timestamps' => true]);
        $pivot = $user->permissions()->first()->pivot;
        $this->assertNotNull($pivot->{$pivot->getCreatedAtColumn()});
        $this->assertNotNull($pivot->{$pivot->getUpdatedAtColumn()});

        // ! ||--------------------------------------------------------------------------------||
        // ! ||        Подтверждаем отсутствие временных меток у промежуточных моделей.        ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.timestamps' => false]);
        $pivot = $user->permissions()->first()->pivot;
        $this->assertNull($pivot->{$pivot->getCreatedAtColumn()});
        $this->assertNull($pivot->{$pivot->getUpdatedAtColumn()});
    }

    /**
     * Если ли метод, возвращающий коллекцию разрешений?
     */
    public function test_get_permissions(): void
    {
        $user = $this->generate($this->user);
        $permissions = $this->generate($this->model, 3);
        $permissions->each(fn ($item) => $user->permissions()->attach($item));
        $expected = $permissions->pluck($this->keyName)->all();

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                         Подтверждаем возврат коллекции.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $user->getPermissions();
        $this->assertInstanceOf(Collection::class, $permissions);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                         Подтверждаем возврат разрешений                        ||
        // ! ||--------------------------------------------------------------------------------||

        $actual = $user->getPermissions()->pluck($this->keyName)->all();
        $this->assertEquals($expected, $actual);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Подтверждаем количество запросов к БД.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $this->resetQueryExecutedCount();
        $user->getPermissions();
        $this->assertQueryExecutedCount(0);
    }

    /**
     * Есть ли метод, подгружающий отношение модели с разрешениями?
     */
    public function test_load_permissions(): void
    {
        $user = $this->generate($this->user);
        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->load('permissions');

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                 Подтверждаем, что отношение не было загружено.                 ||
        // ! ||--------------------------------------------------------------------------------||

        $user->permissions()->detach();
        $condition = $user->permissions->isNotEmpty();
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Подтверждаем, что отношение обновлено.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $user->loadPermissions();
        $condition = $user->permissions->isEmpty();
        $this->assertTrue($condition);
    }

    /**
     * Есть ли метод, присоединяющий разрешение к модели?
     */
    public function test_attach_permission_with_one_param(): void
    {
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getKey();
        $condition = $user->attachPermission($permission);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                             Передаем идентификатор.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getKey();
        $condition = $user->attachPermission($permission);
        $this->assertTrue($condition);
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->getKey() === $permission));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                 Передаем slug.                                 ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getSlug();
        $condition = $user->attachPermission($permission);
        $this->assertTrue($condition);
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->getSlug() === $permission));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                Передаем модель.                                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $condition = $user->attachPermission($permission);
        $this->assertTrue($condition);
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->is($permission)));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||               Повторно передаем присоединенное ранее разрешение.               ||
        // ! ||--------------------------------------------------------------------------------||

        $condition = $user->attachPermission($permission);
        $this->assertFalse($condition);
        $permissions = $user->permissions->where($this->keyName, $permission->getKey());
        $this->assertCount(1, $permissions);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Передаем отсутствующий в таблице slug.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = 'my_slug';
        $condition = $user->attachPermission($permission);
        $this->assertFalse($condition);
        $this->assertFalse($user->permissions->contains(fn ($item) => $item->getSlug() === $permission));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                 Передаем отсутствующий в таблице идентификатор.                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = 634569569;
        $condition = $user->attachPermission($permission);
        $this->assertFalse($condition);
        $this->assertFalse($user->permissions->contains(fn ($item) => $item->getKey() === $permission));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||      при присоединении не присоединенного ранее идентификатора разрешения.     ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getKey();
        $this->resetQueryExecutedCount();
        $user->attachPermission($permission);
        $this->assertQueryExecutedCount(3);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||          при присоединении не присоединенной ранее модели разрешения.          ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $this->resetQueryExecutedCount();
        $this->resetQueries();
        $user->attachPermission($permission);
        $this->assertQueryExecutedCount(2);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||            при присоединении присоединенной ранее модели разрешения.           ||
        // ! ||--------------------------------------------------------------------------------||

        $this->resetQueryExecutedCount();
        $user->attachPermission($permission);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||       при присоединении присоединенного ранее идентификатора разрешения.       ||
        // ! ||--------------------------------------------------------------------------------||

        $this->resetQueryExecutedCount();
        $user->attachPermission($permission->getKey());
        $this->assertQueryExecutedCount(1);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Подтверждаем, что при отключении опции                     ||
        // ! ||            авто обновления отношений, разрешения не были обновлены.            ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.load_on_update' => false]);
        $permission = $this->generate($this->model)->getKey();
        $condition = $user->attachPermission($permission);
        $this->assertTrue($condition);
        $this->assertFalse($user->permissions->contains(fn ($item) => $item->getKey() === $permission));
        $user->loadPermissions();
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->getKey() === $permission));
    }

    /**
     * Есть ли метод, присоединяющий множество разрешений к модели?
     */
    public function test_attach_permission_with_many_params(): void
    {
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $condition = $user->attachPermission($permissions);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив идентификаторов.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $condition = $user->attachPermission(...$permissions);
        $this->assertTrue($condition);
        $this->assertTrue(
            collect($permissions)->every(
                fn ($permission) => $user->permissions->contains(
                    fn ($item) => $item->getKey() === $permission
                )
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                       Передаем коллекцию идентификаторов.                      ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName);
        $condition = $user->attachPermission($permissions);
        $this->assertTrue($condition);
        $this->assertTrue(
            $permissions->every(
                fn ($permission) => $user->permissions->contains(
                    fn ($item) => $item->getKey() === $permission
                )
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив slug'ов.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->slugName)->all();
        $condition = $user->attachPermission(...$permissions);
        $this->assertTrue($condition);
        $this->assertTrue(
            collect($permissions)->every(
                fn ($permission) => $user->permissions->contains(
                    fn ($item) => $item->getSlug() === $permission
                )
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив моделей.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->all();
        $condition = $user->attachPermission($permissions);
        $this->assertTrue($condition);
        $this->assertTrue(
            collect($permissions)->every(
                fn ($permission) => $user->permissions->contains(
                    fn ($item) => $item->is($permission)
                )
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                 Передаем массив уже присоединенных разрешений.                 ||
        // ! ||--------------------------------------------------------------------------------||

        $condition = $user->attachPermission(...$permissions);
        $this->assertFalse($condition);
        $this->assertTrue(
            collect($permissions)->every(
                fn ($permission) => $user->permissions->where($this->keyName, $permission->getKey())->count() === 1
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||            Передаем массив отсутствующих в таблице идентификаторов.            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [354345, '354546544765', '34342'];
        $condition = $user->attachPermission(...$permissions);
        $this->assertFalse($condition);
        $this->assertTrue(
            collect($permissions)->every(
                fn ($permission) => ! $user->permissions->contains(
                    fn ($item) => $item->getKey() === $permission
                )
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                      при передачи массива идентификаторов.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $this->resetQueryExecutedCount();
        $user->attachPermission(...$permissions);
        $this->assertQueryExecutedCount(5);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                          при передачи массива моделей.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->all();
        $this->resetQueryExecutedCount();
        $user->attachPermission($permissions);
        $this->assertQueryExecutedCount(4);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||             при повторной передачи ранее присоединенных разрешений.            ||
        // ! ||--------------------------------------------------------------------------------||

        $this->resetQueryExecutedCount();
        $user->attachPermission($permissions);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||        при передачи отсутствующих в таблице идентификаторов разрешений.        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [354345, '354546544765', '34342'];
        $this->resetQueryExecutedCount();
        $condition = $user->attachPermission(...$permissions);
        $this->assertQueryExecutedCount(1);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||              при отключении подгрузки отношений после обновления.              ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.load_on_update' => false]);
        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $this->resetQueryExecutedCount();
        $user->attachPermission(...$permissions);
        $this->assertQueryExecutedCount(4);
    }

    /**
     * Есть ли метод, отсоединяющий разрешение?
     */
    public function test_detach_permission_with_one_param(): void
    {
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $condition = $user->detachPermission($permission);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                             Передаем идентификатор.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getKey();
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $condition = $user->detachPermission($permission);
        $this->assertTrue($condition);
        $this->assertFalse(
            $user->permissions->contains(fn ($item) => $item->getKey() === $permission)
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                 Передаем slug.                                 ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $condition = $user->detachPermission($permission->getSlug());
        $this->assertTrue($condition);
        $this->assertFalse(
            $user->permissions->contains(fn ($item) => $item->getSlug() === $permission->getSlug())
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                Передаем модель.                                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $condition = $user->detachPermission($permission);
        $this->assertTrue($condition);
        $this->assertFalse(
            $user->permissions->contains(fn ($item) => $item->is($permission))
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Повторно передаем отсоединенную модель.                    ||
        // ! ||--------------------------------------------------------------------------------||

        $condition = $user->detachPermission($permission);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем не присоединенную разрешение.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $condition = $user->detachPermission($permission);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                 Передаем отсутствующий в таблице идентификатор.                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = 4564564564;
        $condition = $user->detachPermission($permission);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                               Ничего не передаем.                              ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $condition = $user->detachPermission();
        $this->assertTrue($condition);
        $this->assertTrue($user->permissions->isEmpty());

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                          при передачи идентификатора.                          ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getKey();
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $this->resetQueryExecutedCount();
        $user->detachPermission($permission);
        $this->assertQueryExecutedCount(3);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                              при передачи модели.                              ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $this->resetQueryExecutedCount();
        $user->detachPermission($permission);
        $this->assertQueryExecutedCount(2);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                     при передачи не присоединенной модели.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $this->resetQueryExecutedCount();
        $user->detachPermission($permission);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||       при отключении автоматической подгрузки отношений после обновления.      ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.load_on_update' => false]);
        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $this->resetQueryExecutedCount();
        $user->detachPermission($permission);
        $this->assertQueryExecutedCount(1);
    }

    /**
     * Есть ли метод, отсоединяющий множество разрешений?
     */
    public function test_detach_permission_with_many_params(): void
    {
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $user->permissions()->attach($permissions);
        $user->loadPermissions();
        $condition = $user->detachPermission($permissions);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив идентификаторов.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $user->permissions()->attach($permissions);
        $user->loadPermissions();
        $condition = $user->detachPermission(...$permissions);
        $this->assertTrue($condition);
        $this->assertTrue(
            collect($permissions)->every(
                fn ($permission) => ! $user->permissions->contains(fn ($item) => $item->getKey() === $permission)
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                       Передаем коллекцию идентификаторов.                      ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $user->permissions()->attach($permissions);
        $user->loadPermissions();
        $condition = $user->detachPermission(collect($permissions));
        $this->assertTrue($condition);
        $this->assertTrue(
            collect($permissions)->every(
                fn ($permission) => ! $user->permissions->contains(fn ($item) => $item->getKey() === $permission)
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив slug'ов.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->permissions()->attach($permissions->pluck($this->keyName)->all());
        $user->loadPermissions();
        $condition = $user->detachPermission(...$permissions->pluck($this->slugName)->all());
        $this->assertTrue($condition);
        $this->assertTrue(
            $permissions->pluck($this->slugName)->every(
                fn ($permission) => ! $user->permissions->contains(fn ($item) => $item->getSlug() === $permission)
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив моделей.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->permissions()->attach($permissions->pluck($this->keyName)->all());
        $user->loadPermissions();
        $condition = $user->detachPermission($permissions->all());
        $this->assertTrue($condition);
        $this->assertTrue(
            $permissions->every(
                fn ($permission) => ! $user->permissions->contains(fn ($item) => $item->is($permission))
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                  Передаем массив не присоединенных разрешений.                 ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $condition = $user->detachPermission(...$permissions);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||            Передаем массив отсутствующих в таблице идентификаторов.            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [485475, '45646', 'sjfldlg'];
        $condition = $user->detachPermission($permissions);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                      при передачи массива идентификаторов.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $user->permissions()->attach($permissions);
        $user->loadPermissions();
        $this->resetQueryExecutedCount();
        $user->detachPermission($permissions);
        $this->assertQueryExecutedCount(5);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                          при передачи массива моделей.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->permissions()->attach($permissions->pluck($this->keyName)->all());
        $user->loadPermissions();
        $this->resetQueryExecutedCount();
        $user->detachPermission($permissions->all());
        $this->assertQueryExecutedCount(4);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||               при передачи массива не присоединенных разрешений.               ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->all();
        $this->resetQueryExecutedCount();
        $user->detachPermission($permissions);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||       при отключении автоматической подгрузки отношений после изменения.       ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.load_on_update' => false]);
        $permissions = $this->generate($this->model, 3);
        $user->permissions()->attach($permissions->pluck($this->keyName)->all());
        $user->loadPermissions();
        $this->resetQueryExecutedCount();
        $user->detachPermission($permissions->all());
        $this->assertQueryExecutedCount(3);
    }

    /**
     * Есть ли метод, отсоединяющий все разрешения?
     */
    public function test_detach_all_permissions(): void
    {
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachPermission($permissions);
        $condition = $user->detachAllPermissions();
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                              Отсоединяем все разрешения.                             ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachPermission($permissions);
        $condition = $user->detachAllPermissions();
        $this->assertTrue($condition);
        $this->assertEmpty($user->permissions);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                         Повторно отсоединяем все разрешения.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $condition = $user->detachAllPermissions();
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                             при наличии разрешений.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachPermission($permissions);
        $this->resetQueryExecutedCount();
        $user->detachAllPermissions();
        $this->assertQueryExecutedCount(2);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                           при отсутствии разрешений.                           ||
        // ! ||--------------------------------------------------------------------------------||

        $this->resetQueryExecutedCount();
        $user->detachAllPermissions();
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||        при отключении автоматической подгрузки отношений при обновлении.       ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.load_on_update' => false]);
        $permissions = $this->generate($this->model, 3);
        $user->attachPermission($permissions);
        $user->loadPermissions();
        $this->resetQueryExecutedCount();
        $user->detachAllPermissions();
        $this->assertQueryExecutedCount(1);
    }

    /**
     * Есть ли метод, синхронизирующий разрешения?
     */
    public function test_sync_permissions(): void
    {
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                             Передаем идентификатор.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $expected = [$permission->getKey()];
        $user->syncPermissions($permission->getKey());
        $actual = $user->permissions->pluck($this->keyName)->all();
        $this->assertEquals($expected, $actual);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                Передаем модель.                                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $expected = [$permission->getKey()];
        $user->syncPermissions($permission);
        $actual = $user->permissions->pluck($this->keyName)->all();
        $this->assertEquals($expected, $actual);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив идентификаторов.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $expected = $permissions->pluck($this->keyName)->all();
        $user->syncPermissions($expected);
        $actual = $user->permissions->pluck($this->keyName)->all();
        $this->assertEquals($expected, $actual);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив моделей.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $expected = $permissions->pluck($this->keyName)->all();
        $user->syncPermissions($permissions);
        $actual = $user->permissions->pluck($this->keyName)->all();
        $this->assertEquals($expected, $actual);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                 Передаем массив несуществующих идентификаторов.                ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [34543, '3453453434', 'sdfgdsg'];
        $user->syncPermissions($permissions);
        $this->assertEmpty($user->permissions);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||               Подтверждаем количество выполненных запросов к БД.               ||
        // ! ||--------------------------------------------------------------------------------||

        $user->attachPermission($this->generate($this->model, 3));
        $permissions = $this->generate($this->model, 3);
        $this->resetQueryExecutedCount();
        $user->syncPermissions($permissions);
        $this->assertQueryExecutedCount(6);
    }

    /**
     * Есть ли метод, проверяющий наличие разрешения у модели?
     */
    public function test_has_one_permission_with_one_param(): void
    {
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $condition = $user->hasPermission($permission);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                             Передаем идентификатор.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getKey();
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $condition = $user->hasPermission($permission);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                 Передаем slug.                                 ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $condition = $user->hasPermission($permission->getSlug());
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                Передаем модель.                                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $condition = $user->hasPermission($permission);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Передаем отсутствующее у модели разрешение.                  ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $condition = $user->hasPermission($permission);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                 Передаем отсутствующий в таблице идентификатор.                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = 384563459;
        $condition = $user->hasPermission($permission);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||       Подтверждаем количество запросов к БД при передаче идентификатора.       ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getKey();
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $this->resetQueryExecutedCount();
        $user->hasPermission($permission);
        $this->assertQueryExecutedCount(1);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||           Подтверждаем количество запросов к БД при передаче модели.           ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $this->resetQueryExecutedCount();
        $user->hasPermission($permission);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                      Подтверждаем количество запросов к БД                     ||
        // ! ||                при передачи отсутствующего у модели разрешения.                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $this->resetQueryExecutedCount();
        $user->hasPermission($permission);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                      Подтверждаем количество запросов к БД                     ||
        // ! ||              при передаче отсутствующего в таблице идентификатора.             ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = 384563459;
        $this->resetQueryExecutedCount();
        $condition = $user->hasPermission($permission);
        $this->assertQueryExecutedCount(1);
    }

    /**
     * Есть ли метод, проверяющий наличие хотябы одного разрешения из переданных.
     */
    public function test_has_one_permission_with_many_params(): void
    {
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachPermission($permissions);
        $condition = $user->hasPermission($permissions);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив идентификаторов.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName);
        $user->attachPermission($permissions);
        $condition = $user->hasPermission($permissions);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив slug'ов.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $this->slugName = app($this->model)->getSlugName();
        $permissions = $this->generate($this->model, 3)->pluck($this->slugName);
        $user->attachPermission($permissions);
        $condition = $user->hasPermission($permissions);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив моделей.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachPermission($permissions);
        $condition = $user->hasPermission($permissions);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||             Передаем массив не присоединенных к модели разрешений.             ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $condition = $user->hasPermission($permissions);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||            Передаем массив отсутствующих в таблице идентификаторов.            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [354354, '3456457', 'dfgdgf'];
        $condition = $user->hasPermission($permissions);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив смешанных данных.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            'dskjghkjdsgf',
            $this->generate($this->model)->getKey(),
            $this->generate($this->model, false),
            $this->generate($this->model)->getSlug(),
            $user->permissions->first(),
        ];
        $condition = $user->hasPermission($permissions);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                      при передаче массива идентификаторов.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $user->attachPermission($permissions);
        $this->resetQueryExecutedCount();
        $user->hasPermission($permissions);
        $this->assertQueryExecutedCount(1);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                          при передаче массива моделей.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachPermission($permissions);
        $this->resetQueryExecutedCount();
        $user->hasPermission($permissions);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||               при передаче не присоединенных к модели разрешений.              ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $this->resetQueryExecutedCount();
        $user->hasPermission($permissions);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||          при передаче массива отсутствующих в таблице идентификаторов.         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [6345437, '3454646', 'dsfgdsgsdgf'];
        $this->resetQueryExecutedCount();
        $user->hasPermission($permissions);
        $this->assertQueryExecutedCount(1);
    }

    /**
     * Есть ли метод, проверяющий наличие всех разрешений у модели?
     */
    public function test_has_all_permissions(): void
    {
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachPermission($permissions);
        $condition = $user->hasPermission($permissions, true);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив идентификаторов.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName);
        $user->attachPermission($permissions);
        $condition = $user->hasPermission($permissions, true);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив slug'ов.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->slugName);
        $user->attachPermission($permissions);
        $condition = $user->hasPermission($permissions, true);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив моделей.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachPermission($permissions);
        $condition = $user->hasPermission($permissions, true);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||             Передаем массив не присоединенных к модели разрешений.             ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $condition = $user->hasPermission($permissions, true);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||            Передаем массив отсутствующих в таблице идентификаторов.            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [354354, '3456457', 'dfgdgf'];
        $condition = $user->hasPermission($permissions, true);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив смешанных данных.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            'dskjghkjdsgf',
            $this->generate($this->model)->getKey(),
            $this->generate($this->model, false),
            $this->generate($this->model)->getSlug(),
            $user->permissions->first(),
        ];
        $condition = $user->hasPermission($permissions, true);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                      при передаче массива идентификаторов.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $user->attachPermission($permissions);
        $this->resetQueryExecutedCount();
        $user->hasPermission($permissions, true);
        $this->assertQueryExecutedCount(1);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                          при передаче массива моделей.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachPermission($permissions);
        $this->resetQueryExecutedCount();
        $user->hasPermission($permissions, true);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||               при передаче не присоединенных к модели разрешений.              ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $this->resetQueryExecutedCount();
        $user->hasPermission($permissions, true);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||          при передаче массива отсутствующих в таблице идентификаторов.         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [6345437, '3454646', 'dsfgdsgsdgf'];
        $this->resetQueryExecutedCount();
        $user->hasPermission($permissions, true);
        $this->assertQueryExecutedCount(1);
    }

    /**
     * Есть ли метод, проверяющий наличие разрешений?
     */
    public function test_can(): void
    {
        config(['can.uses.extend_can_method' => true]);
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $condition = $user->can($permission);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                             Передаем идентификатор.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $condition = $user->can($permission);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив slug'ов.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->slugName)->all();
        $user->attachPermission($permissions);
        $condition = $user->can($permissions, true);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив моделей.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachPermission($permissions->slice(0, 2));
        $condition = $user->can($permissions, true);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                       Передаем отсутствующее разрешение.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $condition = $user->can($permission);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                    Подтверждаем работу метода по умолчанию.                    ||
        // ! ||--------------------------------------------------------------------------------||

        Gate::define('view.profile', fn () => true);
        $condition = $user->can('view.profile');
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||       Подтверждаем работу метода по умолчанию при отключении расширения.       ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.extend_can_method' => false]);
        $permission = $this->generate($this->model)->getKey();
        $user->permissions()->attach($permission);
        $user->loadPermissions();
        $condition = $user->can($permission);
        $this->assertFalse($condition);
    }

    /**
     * Есть ли магический метод, проверяющий наличие разрешения по его slug'у?
     */
    public function test_call_magic_can_permission(): void
    {
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->attachPermission($permission);
        $method = 'can'.ucfirst($permission->getSlug());
        $condition = $user->{$method}();
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Подтверждаем наличие разрешения.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->attachPermission($permission);
        $method = 'can'.ucfirst($permission->getSlug());
        $condition = $user->{$method}();
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                       Подтверждаем отсутствие разрешения.                      ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $method = 'can'.ucfirst($permission->getSlug());
        $condition = $user->{$method}();
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                         Подтверждаем выброс исключения.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $this->expectException(BadMethodCallException::class);
        $user->undefined();
    }
}
