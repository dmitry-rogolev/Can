<?php

namespace dmitryrogolev\Can\Tests\Feature\Traits;

use BadMethodCallException;
use dmitryrogolev\Can\Tests\RefreshDatabase;
use dmitryrogolev\Can\Tests\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

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

    public function setUp(): void
    {
        parent::setUp();

        $this->model = config('can.models.permission');
        $this->user = config('can.models.user');
        $this->keyName = config('can.primary_key');
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
        $level1 = $this->generate($this->model, ['level' => 1]);
        $level2 = $this->generate($this->model, ['level' => 2]);
        $this->generate($this->model, ['level' => 3]);
        $user->permissions()->attach($level2);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                         Подтверждаем возврат коллекции.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $user->getRoles();
        $this->assertInstanceOf(Collection::class, $permissions);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||           Подтверждаем возврат разрешений при отключенной иерархии разрешений.           ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.levels' => false]);
        $expected = [$level2->getKey()];
        $actual = $user->getRoles()->pluck($this->keyName)->all();
        $this->assertEquals($expected, $actual);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||            Подтверждаем возврат разрешений при включенной иерархии разрешений.           ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.levels' => true]);
        $expected = [$level1->getKey(), $level2->getKey()];
        $actual = $user->getRoles()->pluck($this->keyName)->all();
        $this->assertEquals($expected, $actual);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||      Подтверждаем количество запросов к БД при отключенной иерархии разрешений.     ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.levels' => false]);
        $this->resetQueryExecutedCount();
        $user->getRoles();
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||       Подтверждаем количество запросов к БД при включенной иерархии разрешений.      ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.levels' => true]);
        $this->resetQueryExecutedCount();
        $user->getRoles();
        $this->assertQueryExecutedCount(1);
    }

    /**
     * Есть ли метод, подгружающий отношение модели с ролями?
     */
    public function test_load_roles(): void
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

        $user->loadRoles();
        $condition = $user->permissions->isEmpty();
        $this->assertTrue($condition);
    }

    /**
     * Есть ли метод, присоединяющий роль к модели?
     */
    public function test_attach_role_use_levels_with_one_param(): void
    {
        config(['can.uses.levels' => true]);
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);
        $minLevel = 3;
        $level = 2;

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => ++$level])->getKey();
        $condition = $user->attachRole($permission);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                             Передаем идентификатор.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => ++$level])->getKey();
        $condition = $user->attachRole($permission);
        $this->assertTrue($condition);
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->getKey() === $permission));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                 Передаем slug.                                 ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => ++$level])->getSlug();
        $condition = $user->attachRole($permission);
        $this->assertTrue($condition);
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->getSlug() === $permission));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                Передаем модель.                                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => ++$level]);
        $condition = $user->attachRole($permission);
        $this->assertTrue($condition);
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->can($permission)));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Повторно передаем присоединенную роль.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $condition = $user->attachRole($permission);
        $this->assertFalse($condition);
        $permissions = $user->permissions->where($this->keyName, $permission->getKey());
        $this->assertCount(1, $permissions);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||        Передаем роль с уровнем равным максимальному уровню пользователя.       ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => $level]);
        $condition = $user->attachRole($permission);
        $this->assertFalse($condition);
        $permissions = $user->permissions->where('level', $permission->level);
        $this->assertCount(1, $permissions);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                  Передаем роль с меньшим уровнем относительно                  ||
        // ! ||                       максимального уровня пользователя.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => $minLevel - 1]);
        $condition = $user->attachRole($permission);
        $this->assertFalse($condition);
        $permissions = $user->permissions->where('level', $permission->level);
        $this->assertCount(0, $permissions);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Передаем отсутствующий в таблице slug.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = 'my_slug';
        $condition = $user->attachRole($permission);
        $this->assertFalse($condition);
        $this->assertFalse($user->permissions->contains(fn ($item) => $item->getSlug() === $permission));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                 Передаем отсутствующий в таблице идентификатор.                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = 634569569;
        $condition = $user->attachRole($permission);
        $this->assertFalse($condition);
        $this->assertFalse($user->permissions->contains(fn ($item) => $item->getKey() === $permission));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||           при присоединении отсутствующей роли и при передачи модели.          ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => ++$level]);
        $this->resetQueryExecutedCount();
        $this->resetQueries();
        $user->attachRole($permission);
        $this->assertQueryExecutedCount(2);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||       при присоединении отсутствующей роли и при передачи идентификатора.      ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => ++$level])->getKey();
        $this->resetQueryExecutedCount();
        $user->attachRole($permission);
        $this->assertQueryExecutedCount(3);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||               при присоединении существующей у пользователя роли               ||
        // ! ||                             и при передачи модели.                             ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => $level]);
        $this->resetQueryExecutedCount();
        $user->attachRole($permission);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||               при присоединении существующей у пользователя роли               ||
        // ! ||                         и при передачи идентификатора.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => $level])->getKey();
        $this->resetQueryExecutedCount();
        $user->attachRole($permission);
        $this->assertQueryExecutedCount(1);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Подтверждаем, что при отключении опции                     ||
        // ! ||               авто обновления отношений, роли не были обновлены.               ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.load_on_update' => false]);
        $permission = $this->generate($this->model, ['level' => ++$level])->getKey();
        $condition = $user->attachRole($permission);
        $this->assertTrue($condition);
        $this->assertFalse($user->permissions->contains(fn ($item) => $item->getKey() === $permission));
        $user->loadRoles();
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->getKey() === $permission));
    }

    /**
     * Есть ли метод, присоединяющий множество разрешений к модели?
     */
    public function test_attach_role_use_levels_with_many_params(): void
    {
        config(['can.uses.levels' => true]);
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);
        $level = 2;

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
        ];
        $condition = $user->attachRole($permissions);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив идентификаторов.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
        ];
        $condition = $user->attachRole(...$permissions);
        $this->assertTrue($condition);
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->getKey() === last($permissions)));
        $this->assertTrue(
            collect($permissions)->slice(0, count($permissions) - 1)->every(
                fn ($permission) => ! $user->permissions->contains(
                    fn ($item) => $item->getKey() === $permission
                )
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                       Передаем коллекцию идентификаторов.                      ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = collect([
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
        ]);
        $condition = $user->attachRole($permissions);
        $this->assertTrue($condition);
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->getKey() === $permissions->last()));
        $this->assertTrue(
            $permissions->slice(0, $permissions->count() - 1)->every(
                fn ($permission) => ! $user->permissions->contains(
                    fn ($item) => $item->getKey() === $permission
                )
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив slug'ов.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level])->getSlug(),
            $this->generate($this->model, ['level' => ++$level])->getSlug(),
            $this->generate($this->model, ['level' => ++$level])->getSlug(),
        ];
        $condition = $user->attachRole(...$permissions);
        $this->assertTrue($condition);
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->getSlug() === last($permissions)));
        $this->assertTrue(
            collect($permissions)->slice(0, count($permissions) - 1)->every(
                fn ($permission) => ! $user->permissions->contains(
                    fn ($item) => $item->getSlug() === $permission
                )
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив моделей.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
        ];
        $condition = $user->attachRole($permissions);
        $this->assertTrue($condition);
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->can(last($permissions))));
        $this->assertTrue(
            collect($permissions)->slice(0, count($permissions) - 1)->every(
                fn ($permission) => ! $user->permissions->contains(
                    fn ($item) => $item->can($permission)
                )
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                    Передаем массив уже присоединенных разрешений.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $condition = $user->attachRole(...$permissions);
        $this->assertFalse($condition);
        $this->assertTrue($user->permissions->where($this->keyName, last($permissions)->getKey())->count() === 1);
        $this->assertTrue(
            collect($permissions)->slice(0, count($permissions) - 1)->every(
                fn ($permission) => ! $user->permissions->contains(
                    fn ($item) => $item->can($permission)
                )
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Передаем массив разрешений, у которых уровни                    ||
        // ! ||                       равны максимальному уровню модели.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => $level]),
            $this->generate($this->model, ['level' => $level]),
            $this->generate($this->model, ['level' => $level]),
        ];
        $condition = $user->attachRole($permissions);
        $this->assertFalse($condition);
        $this->assertTrue(
            collect($permissions)->every(
                fn ($permission) => $user->permissions->contains(
                    fn ($item) => ! $item->can($permission)
                )
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Передаем массив разрешений, у которых уровни                    ||
        // ! ||                        ниже максимального уровня модели.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => $level - 1]),
            $this->generate($this->model, ['level' => $level - 1]),
            $this->generate($this->model, ['level' => $level - 1]),
        ];
        $condition = $user->attachRole($permissions);
        $this->assertFalse($condition);
        $this->assertTrue(
            collect($permissions)->every(
                fn ($permission) => $user->permissions->contains(
                    fn ($item) => ! $item->can($permission)
                )
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||            Передаем массив отсутствующих в таблице идентификаторов.            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [354345, '354546544765', '34342'];
        $condition = $user->attachRole(...$permissions);
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

        $permissions = [
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
        ];
        $this->resetQueryExecutedCount();
        $user->attachRole(...$permissions);
        $this->assertQueryExecutedCount(3);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                          при передачи массива моделей.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
        ];
        $this->resetQueryExecutedCount();
        $user->attachRole($permissions);
        $this->assertQueryExecutedCount(2);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||               при повторной передачи ранее присоединенных разрешений.               ||
        // ! ||--------------------------------------------------------------------------------||

        $this->resetQueryExecutedCount();
        $user->attachRole($permissions);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                      при передачи массива разрешений с уровнем                      ||
        // ! ||                        ниже максимального уровня модели.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => $level - 1]),
            $this->generate($this->model, ['level' => $level - 1]),
            $this->generate($this->model, ['level' => $level - 1]),
        ];
        $this->resetQueryExecutedCount();
        $user->attachRole($permissions);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||           при передачи отсутствующих в таблице идентификаторов разрешений.          ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [354345, '354546544765', '34342'];
        $this->resetQueryExecutedCount();
        $condition = $user->attachRole(...$permissions);
        $this->assertQueryExecutedCount(1);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||              при отключении подгрузки отношений после обновления.              ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.load_on_update' => false]);
        $permissions = [
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
        ];
        $this->resetQueryExecutedCount();
        $user->attachRole(...$permissions);
        $this->assertQueryExecutedCount(2);
    }

    /**
     * Есть ли метод, присоединяющий роль к модели?
     */
    public function test_attach_role_without_levels_with_one_param(): void
    {
        config(['can.uses.levels' => false]);
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getKey();
        $condition = $user->attachRole($permission);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                             Передаем идентификатор.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getKey();
        $condition = $user->attachRole($permission);
        $this->assertTrue($condition);
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->getKey() === $permission));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                 Передаем slug.                                 ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getSlug();
        $condition = $user->attachRole($permission);
        $this->assertTrue($condition);
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->getSlug() === $permission));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                Передаем модель.                                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $condition = $user->attachRole($permission);
        $this->assertTrue($condition);
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->can($permission)));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Повторно передаем присоединенную роль.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $condition = $user->attachRole($permission);
        $this->assertFalse($condition);
        $permissions = $user->permissions->where($this->keyName, $permission->getKey());
        $this->assertCount(1, $permissions);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Передаем отсутствующий в таблице slug.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = 'my_slug';
        $condition = $user->attachRole($permission);
        $this->assertFalse($condition);
        $this->assertFalse($user->permissions->contains(fn ($item) => $item->getSlug() === $permission));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                 Передаем отсутствующий в таблице идентификатор.                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = 634569569;
        $condition = $user->attachRole($permission);
        $this->assertFalse($condition);
        $this->assertFalse($user->permissions->contains(fn ($item) => $item->getKey() === $permission));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||       при присоединении отсутствующей роли и при передачи идентификатора.      ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getKey();
        $this->resetQueryExecutedCount();
        $user->attachRole($permission);
        $this->assertQueryExecutedCount(3);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||           при присоединении отсутствующей роли и при передачи модели.          ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $this->resetQueryExecutedCount();
        $this->resetQueries();
        $user->attachRole($permission);
        $this->assertQueryExecutedCount(2);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||               при присоединении существующей у пользователя роли               ||
        // ! ||                             и при передачи модели.                             ||
        // ! ||--------------------------------------------------------------------------------||

        $this->resetQueryExecutedCount();
        $user->attachRole($permission);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||               при присоединении существующей у пользователя роли               ||
        // ! ||                         и при передачи идентификатора.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $this->resetQueryExecutedCount();
        $user->attachRole($permission->getKey());
        $this->assertQueryExecutedCount(1);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Подтверждаем, что при отключении опции                     ||
        // ! ||               авто обновления отношений, роли не были обновлены.               ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.load_on_update' => false]);
        $permission = $this->generate($this->model)->getKey();
        $condition = $user->attachRole($permission);
        $this->assertTrue($condition);
        $this->assertFalse($user->permissions->contains(fn ($item) => $item->getKey() === $permission));
        $user->loadRoles();
        $this->assertTrue($user->permissions->contains(fn ($item) => $item->getKey() === $permission));
    }

    /**
     * Есть ли метод, присоединяющий множество разрешений к модели?
     */
    public function test_attach_role_without_levels_with_many_params(): void
    {
        config(['can.uses.levels' => false]);
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model)->getKey(),
            $this->generate($this->model)->getKey(),
            $this->generate($this->model)->getKey(),
        ];
        $condition = $user->attachRole($permissions);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив идентификаторов.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model)->getKey(),
            $this->generate($this->model)->getKey(),
            $this->generate($this->model)->getKey(),
        ];
        $condition = $user->attachRole(...$permissions);
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

        $permissions = collect([
            $this->generate($this->model)->getKey(),
            $this->generate($this->model)->getKey(),
            $this->generate($this->model)->getKey(),
        ]);
        $condition = $user->attachRole($permissions);
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

        $permissions = [
            $this->generate($this->model)->getSlug(),
            $this->generate($this->model)->getSlug(),
            $this->generate($this->model)->getSlug(),
        ];
        $condition = $user->attachRole(...$permissions);
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

        $permissions = [
            $this->generate($this->model),
            $this->generate($this->model),
            $this->generate($this->model),
        ];
        $condition = $user->attachRole($permissions);
        $this->assertTrue($condition);
        $this->assertTrue(
            collect($permissions)->every(
                fn ($permission) => $user->permissions->contains(
                    fn ($item) => $item->can($permission)
                )
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                    Передаем массив уже присоединенных разрешений.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $condition = $user->attachRole(...$permissions);
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
        $condition = $user->attachRole(...$permissions);
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

        $permissions = [
            $this->generate($this->model)->getKey(),
            $this->generate($this->model)->getKey(),
            $this->generate($this->model)->getKey(),
        ];
        $this->resetQueryExecutedCount();
        $user->attachRole(...$permissions);
        $this->assertQueryExecutedCount(5);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                          при передачи массива моделей.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model),
            $this->generate($this->model),
            $this->generate($this->model),
        ];
        $this->resetQueryExecutedCount();
        $user->attachRole($permissions);
        $this->assertQueryExecutedCount(4);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||               при повторной передачи ранее присоединенных разрешений.               ||
        // ! ||--------------------------------------------------------------------------------||

        $this->resetQueryExecutedCount();
        $user->attachRole($permissions);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||           при передачи отсутствующих в таблице идентификаторов разрешений.          ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [354345, '354546544765', '34342'];
        $this->resetQueryExecutedCount();
        $condition = $user->attachRole(...$permissions);
        $this->assertQueryExecutedCount(1);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||              при отключении подгрузки отношений после обновления.              ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.load_on_update' => false]);
        $permissions = [
            $this->generate($this->model)->getKey(),
            $this->generate($this->model)->getKey(),
            $this->generate($this->model)->getKey(),
        ];
        $this->resetQueryExecutedCount();
        $user->attachRole(...$permissions);
        $this->assertQueryExecutedCount(4);
    }

    /**
     * Есть ли метод, отсоединяющий роль?
     */
    public function test_detach_role_with_one_param(): void
    {
        config(['can.uses.levels' => false]);
        config(['can.uses.load_on_update' => true]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->detachRole($permission);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                             Передаем идентификатор.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getKey();
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->detachRole($permission);
        $this->assertTrue($condition);
        $this->assertFalse(
            $user->permissions->contains(fn ($item) => $item->getKey() === $permission)
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                 Передаем slug.                                 ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->detachRole($permission->getSlug());
        $this->assertTrue($condition);
        $this->assertFalse(
            $user->permissions->contains(fn ($item) => $item->getSlug() === $permission->getSlug())
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                Передаем модель.                                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->detachRole($permission);
        $this->assertTrue($condition);
        $this->assertFalse(
            $user->permissions->contains(fn ($item) => $item->can($permission))
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Повторно передаем отсоединенную модель.                    ||
        // ! ||--------------------------------------------------------------------------------||

        $condition = $user->detachRole($permission);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем не присоединенную роль.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $condition = $user->detachRole($permission);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                 Передаем отсутствующий в таблице идентификатор.                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = 4564564564;
        $condition = $user->detachRole($permission);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                               Ничего не передаем.                              ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->detachRole();
        $this->assertTrue($condition);
        $this->assertTrue($user->permissions->isEmpty());

        // ! ||--------------------------------------------------------------------------------||
        // ! ||               Передаем роль, которая фактически не присоединена,               ||
        // ! ||             но присутствует у модели при включении иерархии разрешений.             ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.levels' => true]);
        $user->permissions()->detach();
        $level1 = $this->generate($this->model, ['level' => 1]);
        $level2 = $this->generate($this->model, ['level' => 2]);
        $user->permissions()->attach($level2);
        $user->loadRoles();
        $condition = $user->detachRole($level1);
        $this->assertFalse($condition);
        config(['can.uses.levels' => false]);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                          при передачи идентификатора.                          ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getKey();
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $this->resetQueryExecutedCount();
        $user->detachRole($permission);
        $this->assertQueryExecutedCount(3);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                              при передачи модели.                              ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $this->resetQueryExecutedCount();
        $user->detachRole($permission);
        $this->assertQueryExecutedCount(2);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                     при передачи не присоединенной модели.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $this->resetQueryExecutedCount();
        $user->detachRole($permission);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||       при отключении автоматической подгрузки отношений после обновления.      ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.load_on_update' => false]);
        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $this->resetQueryExecutedCount();
        $user->detachRole($permission);
        $this->assertQueryExecutedCount(1);
    }

    /**
     * Есть ли метод, отсоединяющий множество разрешений?
     */
    public function test_detach_role_with_many_params(): void
    {
        config(['can.uses.load_on_update' => true]);
        config(['can.uses.levels' => false]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $user->permissions()->attach($permissions);
        $user->loadRoles();
        $condition = $user->detachRole($permissions);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив идентификаторов.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $user->permissions()->attach($permissions);
        $user->loadRoles();
        $condition = $user->detachRole(...$permissions);
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
        $user->loadRoles();
        $condition = $user->detachRole(collect($permissions));
        $this->assertTrue($condition);
        $this->assertTrue(
            collect($permissions)->every(
                fn ($permission) => ! $user->permissions->contains(fn ($item) => $item->getKey() === $permission)
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив slug'ов.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $slugName = app($this->model)->getSlugName();
        $permissions = $this->generate($this->model, 3);
        $user->permissions()->attach($permissions->pluck($this->keyName)->all());
        $user->loadRoles();
        $condition = $user->detachRole(...$permissions->pluck($slugName)->all());
        $this->assertTrue($condition);
        $this->assertTrue(
            $permissions->pluck($slugName)->every(
                fn ($permission) => ! $user->permissions->contains(fn ($item) => $item->getSlug() === $permission)
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив моделей.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->permissions()->attach($permissions->pluck($this->keyName)->all());
        $user->loadRoles();
        $condition = $user->detachRole($permissions->all());
        $this->assertTrue($condition);
        $this->assertTrue(
            $permissions->every(
                fn ($permission) => ! $user->permissions->contains(fn ($item) => $item->can($permission))
            )
        );

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                    Передаем массив не присоединенных разрешений.                    ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $condition = $user->detachRole(...$permissions);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||            Передаем массив отсутствующих в таблице идентификаторов.            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [485475, '45646', 'sjfldlg'];
        $condition = $user->detachRole($permissions);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||       Передаем массив разрешений с уровнями ниже максимального уровня модели.       ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.levels' => true]);
        $level1 = $this->generate($this->model, ['level' => 1]);
        $level2 = $this->generate($this->model, ['level' => 2]);
        $level3 = $this->generate($this->model, ['level' => 3]);
        $user->permissions()->attach($level3);
        $user->loadRoles();
        $condition = $user->detachRole($level1, $level2);
        $this->assertFalse($condition);
        config(['can.uses.levels' => false]);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                      при передачи массива идентификаторов.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $user->permissions()->attach($permissions);
        $user->loadRoles();
        $this->resetQueryExecutedCount();
        $user->detachRole($permissions);
        $this->assertQueryExecutedCount(5);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                          при передачи массива моделей.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->permissions()->attach($permissions->pluck($this->keyName)->all());
        $user->loadRoles();
        $this->resetQueryExecutedCount();
        $user->detachRole($permissions->all());
        $this->assertQueryExecutedCount(4);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                  при передачи массива не присоединенных разрешений.                 ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->all();
        $this->resetQueryExecutedCount();
        $user->detachRole($permissions);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||       при отключении автоматической подгрузки отношений после изменения.       ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.load_on_update' => false]);
        $permissions = $this->generate($this->model, 3);
        $user->permissions()->attach($permissions->pluck($this->keyName)->all());
        $user->loadRoles();
        $this->resetQueryExecutedCount();
        $user->detachRole($permissions->all());
        $this->assertQueryExecutedCount(3);
    }

    /**
     * Есть ли метод, отсоединяющий все роли?
     */
    public function test_detach_all_roles(): void
    {
        config(['can.uses.load_on_update' => true]);
        config(['can.uses.levels' => false]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachRole($permissions);
        $condition = $user->detachAllRoles();
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                              Отсоединяем все роли.                             ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachRole($permissions);
        $condition = $user->detachAllRoles();
        $this->assertTrue($condition);
        $this->assertEmpty($user->permissions);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                         Повторно отсоединяем все роли.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $condition = $user->detachAllRoles();
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||      Подтверждаем количество выполненных запросов к БД при наличии разрешений.      ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachRole($permissions);
        $this->resetQueryExecutedCount();
        $user->detachAllRoles();
        $this->assertQueryExecutedCount(2);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                              при отсутствии разрешений.                             ||
        // ! ||--------------------------------------------------------------------------------||

        $this->resetQueryExecutedCount();
        $user->detachAllRoles();
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||        при отключении автоматической подгрузки отношений при обновлении.       ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.load_on_update' => false]);
        $permissions = $this->generate($this->model, 3);
        $user->attachRole($permissions);
        $user->loadRoles();
        $this->resetQueryExecutedCount();
        $user->detachAllRoles();
        $this->assertQueryExecutedCount(1);
    }

    /**
     * Есть ли метод, синхронизирующий роли?
     */
    public function test_sync_roles(): void
    {
        config(['can.uses.load_on_update' => true]);
        config(['can.uses.levels' => false]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                             Передаем идентификатор.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $expected = [$permission->getKey()];
        $user->syncRoles($permission->getKey());
        $actual = $user->permissions->pluck($this->keyName)->all();
        $this->assertEquals($expected, $actual);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                Передаем модель.                                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $expected = [$permission->getKey()];
        $user->syncRoles($permission);
        $actual = $user->permissions->pluck($this->keyName)->all();
        $this->assertEquals($expected, $actual);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив идентификаторов.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $expected = $permissions->pluck($this->keyName)->all();
        $user->syncRoles($expected);
        $actual = $user->permissions->pluck($this->keyName)->all();
        $this->assertEquals($expected, $actual);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив моделей.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $expected = $permissions->pluck($this->keyName)->all();
        $user->syncRoles($permissions);
        $actual = $user->permissions->pluck($this->keyName)->all();
        $this->assertEquals($expected, $actual);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                 Передаем массив несуществующих идентификаторов.                ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [34543, '3453453434', 'sdfgdsg'];
        $user->syncRoles($permissions);
        $this->assertEmpty($user->permissions);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||               Подтверждаем количество выполненных запросов к БД.               ||
        // ! ||--------------------------------------------------------------------------------||

        $user->attachRole($this->generate($this->model, 3));
        $permissions = $this->generate($this->model, 3);
        $this->resetQueryExecutedCount();
        $user->syncRoles($permissions);
        $this->assertQueryExecutedCount(6);
    }

    /**
     * Есть ли метод, проверяющий наличие роли?
     */
    public function test_has_one_role_use_levels_with_one_param(): void
    {
        config(['can.uses.load_on_update' => true]);
        config(['can.uses.levels' => true]);
        $user = $this->generate($this->user);
        $level = 2;

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => ++$level]);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->hasRole($permission);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                             Передаем идентификатор.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => ++$level])->getKey();
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->hasRole($permission);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                 Передаем slug.                                 ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => ++$level]);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->hasRole($permission->getSlug());
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                Передаем модель.                                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => ++$level]);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->hasRole($permission);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||         Передаем роль, равную по уровню с максимальным уровнем модели.         ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => $level]);
        $condition = $user->hasRole($permission);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем роль, меньшую по уровню                        ||
        // ! ||                    относительно максимального уровня модели.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => $level - 1]);
        $condition = $user->hasRole($permission);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем роль, большую по уровню                        ||
        // ! ||                    относительно максимального уровня модели.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => $level + 1]);
        $condition = $user->hasRole($permission);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                 Передаем отсутствующий в таблице идентификатор.                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = 384563459;
        $condition = $user->hasRole($permission);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||       Подтверждаем количество запросов к БД при передаче идентификатора.       ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => ++$level])->getKey();
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $this->resetQueryExecutedCount();
        $user->hasRole($permission);
        $this->assertQueryExecutedCount(1);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||           Подтверждаем количество запросов к БД при передаче модели.           ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => ++$level]);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $this->resetQueryExecutedCount();
        $user->hasRole($permission);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||            Подтверждаем количество запросов к БД при передаче роли,            ||
        // ! ||        имеющую уровень меньший относительно максимального уровня модели.       ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model, ['level' => $level - 1]);
        $this->resetQueryExecutedCount();
        $user->hasRole($permission);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                      Подтверждаем количество запросов к БД                     ||
        // ! ||              при передаче отсутствующего в таблице идентификатора.             ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = 384563459;
        $this->resetQueryExecutedCount();
        $condition = $user->hasRole($permission);
        $this->assertQueryExecutedCount(1);
    }

    /**
     * Есть ли метод, проверяющий наличие роли у модели?
     */
    public function test_has_one_role_without_levels_with_one_param(): void
    {
        config(['can.uses.load_on_update' => true]);
        config(['can.uses.levels' => false]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->hasRole($permission);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                             Передаем идентификатор.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getKey();
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->hasRole($permission);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                 Передаем slug.                                 ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->hasRole($permission->getSlug());
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                                Передаем модель.                                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->hasRole($permission);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                      Передаем отсутствующую у модели роль.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $condition = $user->hasRole($permission);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                 Передаем отсутствующий в таблице идентификатор.                ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = 384563459;
        $condition = $user->hasRole($permission);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||       Подтверждаем количество запросов к БД при передаче идентификатора.       ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model)->getKey();
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $this->resetQueryExecutedCount();
        $user->hasRole($permission);
        $this->assertQueryExecutedCount(1);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||           Подтверждаем количество запросов к БД при передаче модели.           ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $this->resetQueryExecutedCount();
        $user->hasRole($permission);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                      Подтверждаем количество запросов к БД                     ||
        // ! ||                    при передачи отсутствующей у модели роли.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $this->resetQueryExecutedCount();
        $user->hasRole($permission);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                      Подтверждаем количество запросов к БД                     ||
        // ! ||              при передаче отсутствующего в таблице идентификатора.             ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = 384563459;
        $this->resetQueryExecutedCount();
        $condition = $user->hasRole($permission);
        $this->assertQueryExecutedCount(1);
    }

    /**
     * Есть ли метод, проверяющий наличие хотябы одной роли из переданных.
     */
    public function test_has_one_role_with_levels_with_many_params(): void
    {
        config(['can.uses.load_on_update' => true]);
        config(['can.uses.levels' => true]);
        $user = $this->generate($this->user);
        $level = 2;

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
        ];
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив идентификаторов.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
        ];
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив slug'ов.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level])->getSlug(),
            $this->generate($this->model, ['level' => ++$level])->getSlug(),
            $this->generate($this->model, ['level' => ++$level])->getSlug(),
        ];
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив моделей.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
        ];
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Передаем массив разрешений, у которых уровни                    ||
        // ! ||                       равны максимальному уровню модели.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => $level]),
            $this->generate($this->model, ['level' => $level]),
            $this->generate($this->model, ['level' => $level]),
        ];
        $condition = $user->hasRole($permissions);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Передаем массив разрешений, у которых уровни                    ||
        // ! ||                       меньше максимального уровня модели.                      ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => $level - 1]),
            $this->generate($this->model, ['level' => $level - 1]),
            $this->generate($this->model, ['level' => $level - 1]),
        ];
        $condition = $user->hasRole($permissions);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Передаем массив разрешений, у которых уровни                    ||
        // ! ||                        выше максимального уровня модели.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => $level + 1]),
            $this->generate($this->model, ['level' => $level + 1]),
            $this->generate($this->model, ['level' => $level + 1]),
        ];
        $condition = $user->hasRole($permissions);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||            Передаем массив отсутствующих в таблице идентификаторов.            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [354354, '3456457', 'dfgdgf'];
        $condition = $user->hasRole($permissions);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив смешанных данных.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            'dskjghkjdsgf',
            $this->generate($this->model, ['level' => $level + 1])->getKey(),
            $this->generate($this->model, ['level' => $level], false),
            $this->generate($this->model, ['level' => $level - 1])->getSlug(),
        ];
        $condition = $user->hasRole($permissions);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                      при передаче массива идентификаторов.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
        ];
        $user->attachRole($permissions);
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions);
        $this->assertQueryExecutedCount(1);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                          при передаче массива моделей.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
        ];
        $user->attachRole($permissions);
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                  при передаче массива разрешений, у которых уровни                  ||
        // ! ||                        ниже максимального уровня модели.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => $level - 1]),
            $this->generate($this->model, ['level' => $level - 1]),
            $this->generate($this->model, ['level' => $level - 1]),
        ];
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                  при передаче массива разрешений, у которых уровни                  ||
        // ! ||                        выше максимального уровня модели.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => $level + 1]),
            $this->generate($this->model, ['level' => $level + 1]),
            $this->generate($this->model, ['level' => $level + 1]),
        ];
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||          при передаче массива отсутствующих в таблице идентификаторов.         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [6345437, '3454646', 'dsfgdsgsdgf'];
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions);
        $this->assertQueryExecutedCount(1);
    }

    /**
     * Есть ли метод, проверяющий наличие хотябы одной роли из переданных.
     */
    public function test_has_one_role_without_levels_with_many_params(): void
    {
        config(['can.uses.load_on_update' => true]);
        config(['can.uses.levels' => false]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив идентификаторов.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName);
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив slug'ов.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $slugName = app($this->model)->getSlugName();
        $permissions = $this->generate($this->model, 3)->pluck($slugName);
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив моделей.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Передаем массив не присоединенных к модели разрешений.               ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $condition = $user->hasRole($permissions);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||            Передаем массив отсутствующих в таблице идентификаторов.            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [354354, '3456457', 'dfgdgf'];
        $condition = $user->hasRole($permissions);
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
        $condition = $user->hasRole($permissions);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                      при передаче массива идентификаторов.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $user->attachRole($permissions);
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions);
        $this->assertQueryExecutedCount(1);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                          при передаче массива моделей.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachRole($permissions);
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                 при передаче не присоединенных к модели разрешений.                 ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||          при передаче массива отсутствующих в таблице идентификаторов.         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [6345437, '3454646', 'dsfgdsgsdgf'];
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions);
        $this->assertQueryExecutedCount(1);
    }

    /**
     * Есть ли метод, проверяющий наличие всех переданных разрешений?
     */
    public function test_has_all_roles_use_levels(): void
    {
        config(['can.uses.load_on_update' => true]);
        config(['can.uses.levels' => true]);
        $user = $this->generate($this->user);
        $level = 2;

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
        ];
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions, true);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив идентификаторов.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
        ];
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions, true);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив slug'ов.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level])->getSlug(),
            $this->generate($this->model, ['level' => ++$level])->getSlug(),
            $this->generate($this->model, ['level' => ++$level])->getSlug(),
        ];
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions, true);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив моделей.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
        ];
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions, true);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Передаем массив разрешений, у которых уровни                    ||
        // ! ||                       равны максимальному уровню модели.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => $level]),
            $this->generate($this->model, ['level' => $level]),
            $this->generate($this->model, ['level' => $level]),
        ];
        $condition = $user->hasRole($permissions, true);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Передаем массив разрешений, у которых уровни                    ||
        // ! ||                       меньше максимального уровня модели.                      ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => $level - 1]),
            $this->generate($this->model, ['level' => $level - 1]),
            $this->generate($this->model, ['level' => $level - 1]),
        ];
        $condition = $user->hasRole($permissions, true);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                     Передаем массив разрешений, у которых уровни                    ||
        // ! ||                        выше максимального уровня модели.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => $level + 1]),
            $this->generate($this->model, ['level' => $level + 1]),
            $this->generate($this->model, ['level' => $level + 1]),
        ];
        $condition = $user->hasRole($permissions, true);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||            Передаем массив отсутствующих в таблице идентификаторов.            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [354354, '3456457', 'dfgdgf'];
        $condition = $user->hasRole($permissions, true);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив смешанных данных.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            'dskjghkjdsgf',
            $this->generate($this->model, ['level' => $level + 1])->getKey(),
            $this->generate($this->model, ['level' => $level], false),
            $this->generate($this->model, ['level' => $level - 1])->getSlug(),
        ];
        $condition = $user->hasRole($permissions, true);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                      при передаче массива идентификаторов.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
            $this->generate($this->model, ['level' => ++$level])->getKey(),
        ];
        $user->attachRole($permissions);
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions, true);
        $this->assertQueryExecutedCount(1);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                          при передаче массива моделей.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
            $this->generate($this->model, ['level' => ++$level]),
        ];
        $user->attachRole($permissions);
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions, true);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                  при передаче массива разрешений, у которых уровни                  ||
        // ! ||                        ниже максимального уровня модели.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => $level - 1]),
            $this->generate($this->model, ['level' => $level - 1]),
            $this->generate($this->model, ['level' => $level - 1]),
        ];
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions, true);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                  при передаче массива разрешений, у которых уровни                  ||
        // ! ||                        выше максимального уровня модели.                       ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model, ['level' => $level + 1]),
            $this->generate($this->model, ['level' => $level + 1]),
            $this->generate($this->model, ['level' => $level + 1]),
        ];
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions, true);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||          при передаче массива отсутствующих в таблице идентификаторов.         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [6345437, '3454646', 'dsfgdsgsdgf'];
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions, true);
        $this->assertQueryExecutedCount(1);
    }

    /**
     * Есть ли метод, проверяющий наличие всех разрешений у модели?
     */
    public function test_has_all_roles_without_levels(): void
    {
        config(['can.uses.load_on_update' => true]);
        config(['can.uses.levels' => false]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions, true);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                        Передаем массив идентификаторов.                        ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName);
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions, true);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив slug'ов.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $slugName = app($this->model)->getSlugName();
        $permissions = $this->generate($this->model, 3)->pluck($slugName);
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions, true);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив моделей.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachRole($permissions);
        $condition = $user->hasRole($permissions, true);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Передаем массив не присоединенных к модели разрешений.               ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $condition = $user->hasRole($permissions, true);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||            Передаем массив отсутствующих в таблице идентификаторов.            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [354354, '3456457', 'dfgdgf'];
        $condition = $user->hasRole($permissions, true);
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
        $condition = $user->hasRole($permissions, true);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                      при передаче массива идентификаторов.                     ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3)->pluck($this->keyName)->all();
        $user->attachRole($permissions);
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions, true);
        $this->assertQueryExecutedCount(1);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                          при передаче массива моделей.                         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $user->attachRole($permissions);
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions, true);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||                 при передаче не присоединенных к модели разрешений.                 ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = $this->generate($this->model, 3);
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions, true);
        $this->assertQueryExecutedCount(0);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем количество выполненных запросов к БД               ||
        // ! ||          при передаче массива отсутствующих в таблице идентификаторов.         ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [6345437, '3454646', 'dsfgdsgsdgf'];
        $this->resetQueryExecutedCount();
        $user->hasRole($permissions, true);
        $this->assertQueryExecutedCount(1);
    }

    /**
     * Есть ли метод, возвращающий роль модели с максимальным уровнем доступа.
     */
    public function test_role(): void
    {
        config(['can.uses.load_on_update' => true]);
        config(['can.uses.levels' => true]);
        $user = $this->generate($this->user);
        $level1 = $this->generate($this->model, ['level' => 1]);
        $level2 = $this->generate($this->model, ['level' => 2]);
        $level3 = $this->generate($this->model, ['level' => 3]);
        $user->permissions()->attach($level2);
        $user->loadRoles();

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                          Подтверждаем возврат модели.                          ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $user->permission();
        $this->assertInstanceOf($this->model, $permission);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем возврат роли с максимальным уровнем.               ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $user->permission();
        $this->assertTrue($level2->can($permission));

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                 Подтверждаем возврат null при отсутствии разрешений.                ||
        // ! ||--------------------------------------------------------------------------------||

        $user->permissions()->detach();
        $user->loadRoles();
        $permission = $user->permission();
        $this->assertNull($permission);
    }

    /**
     * Есть ли метод, возвращающий наибольший уровень присоединенных разрешений.
     */
    public function test_level(): void
    {
        config(['can.uses.load_on_update' => true]);
        config(['can.uses.levels' => true]);
        $user = $this->generate($this->user);
        $level1 = $this->generate($this->model, ['level' => 1]);
        $level2 = $this->generate($this->model, ['level' => 2]);
        $level3 = $this->generate($this->model, ['level' => 3]);
        $user->permissions()->attach($level2);
        $user->loadRoles();

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                           Подтверждаем возврат числа.                          ||
        // ! ||--------------------------------------------------------------------------------||

        $level = $user->level();
        $this->assertIsInt($level);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                Подтверждаем возврат максимального уровня разрешений.                ||
        // ! ||--------------------------------------------------------------------------------||

        $level = $user->level();
        $this->assertEquals($level2->level, $level);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||         Подтверждаем возврат нуля при отсутствии присоединенных разрешений.         ||
        // ! ||--------------------------------------------------------------------------------||

        $user->permissions()->detach();
        $user->loadRoles();
        $level = $user->level();
        $this->assertEquals(0, $user->level());
    }

    /**
     * Есть ли метод, проверяющий наличие разрешений?
     */
    public function test_is(): void
    {
        config(['can.uses.extend_is_method' => true]);
        config(['can.uses.load_on_update' => true]);
        config(['can.uses.levels' => false]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->can($permission);
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                             Передаем идентификатор.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->can($permission);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив slug'ов.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = [
            $this->generate($this->model)->getSlug(),
            $this->generate($this->model)->getSlug(),
            $this->generate($this->model)->getSlug(),
        ];
        $user->attachRole($permissions);
        $condition = $user->can($permissions, true);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                            Передаем массив моделей.                            ||
        // ! ||--------------------------------------------------------------------------------||

        $permissions = collect([
            $this->generate($this->model),
            $this->generate($this->model),
            $this->generate($this->model),
        ]);
        $user->attachRole($permissions->slice(0, 2));
        $condition = $user->can($permissions, true);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                          Передаем отсутствующую роль.                          ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $condition = $user->can($permission);
        $this->assertFalse($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                    Подтверждаем работу метода по умолчанию.                    ||
        // ! ||--------------------------------------------------------------------------------||

        $condition = $user->can($user);
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||       Подтверждаем работу метода по умолчанию при отключении расширения.       ||
        // ! ||--------------------------------------------------------------------------------||

        config(['can.uses.extend_is_method' => false]);
        $permission = $this->generate($this->model);
        $user->permissions()->attach($permission);
        $user->loadRoles();
        $condition = $user->can($permission);
        $this->assertFalse($condition);
    }

    /**
     * Есть ли магический метод, проверяющий наличие роли по его slug'у?
     */
    public function test_call_magic_is_role(): void
    {
        config(['can.uses.load_on_update' => true]);
        config(['can.uses.levels' => false]);
        $user = $this->generate($this->user);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                   Подтверждаем возврат логического значения.                   ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->attachRole($permission);
        $method = 'can'.ucfirst($permission->getSlug());
        $condition = $user->{$method}();
        $this->assertIsBool($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                           Подтверждаем наличие роли.                           ||
        // ! ||--------------------------------------------------------------------------------||

        $permission = $this->generate($this->model);
        $user->attachRole($permission);
        $method = 'can'.ucfirst($permission->getSlug());
        $condition = $user->{$method}();
        $this->assertTrue($condition);

        // ! ||--------------------------------------------------------------------------------||
        // ! ||                          Подтверждаем отсутствие роли.                         ||
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
