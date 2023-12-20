# Can

Функционал разрешений для фреймворка Laravel.

## Содержание

1. [Подключение](#подключение)

    - [Публикация ресурсов](#публикация-ресурсов)

2. [Перед использованием](#перед-использованием)

    - [Миграции](#миграции)
    - [Сидеры](#сидеры)
    - [Добавление функционала разрешений модели](#добавление-функционала-разрешений-модели)

3. [Использование](#использование)

    - [Прикрепление разрешения](#прикрепление-разрешения)
    - [Отсоединение разрешения](#отсоединение-разрешения)
    - [Отсоединение всех разрешений](#отсоединение-всех-разрешений)
    - [Синхронизация разрешений](#синхронизация-разрешений)
    - [Проверка наличия разрешения](#проверка-наличия-разрешения)
    - [Проверка наличия всех разрешений](#проверка-наличия-всех-разрешений)
    - [Уровни разрешений](#уровни-разрешений)
    - [Расширения Blade](#расширения-blade)
    - [Посредники](#посредники)

4. [Титры](#титры)
5. [Лицензия](#лицензия)

## Подключение 

Добавьте ссылку на репозиторий в файл `composer.json`

    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:dmitry-rogolev/Can.git"
        }
    ]

Подключите пакет с помощью команды: 

    composer require dmitryrogolev/can

### Публикация ресурсов

Вы можете опубликовать все ресурсы разом

    php artisan can:install 

или по отдельности.

    php artisan can:install --config
    php artisan can:install --migrations
    php artisan can:install --seeders

## Перед использованием

### Миграции 

Таблица разрешений имеет следующие основные столбцы:

| Столбец     | Назначение                                                 |
|-------------|------------------------------------------------------------|
| name        | Название разрешения.                                       |
| slug        | Человеко-понятный идентификатор. Например, `create.users`. |
| description | Описание [опционально].                                    |

Создайте таблицу разрешений командой 

    php artisan migrate

### Сидеры

Если вы опубликовали сидеры, то вы можете открыть файл `database/seeders/PermissionSeeder.php` и изменить создаваемые разрешения. 

Затем заполнить таблицу разрешений командой 

    php artisan db:seed PermissionSeeder

### Добавление функционала разрешений модели

В примере ниже мы будем добавлять функционал разрешений модели пользователя. Вы же можете добавить данный функционал любой другой модели или нескольким моделям. 

Добавим трейт `dmitryrogolev\Can\Traits\HasPermissions` и реализуем интерфейс `dmitryrogolev\Can\Contracts\Permissionable` в модели.

    <?php

    namespace App\Models;

    use dmitryrogolev\Can\Contracts\Permissionable;
    use dmitryrogolev\Can\Traits\HasPermissions;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Foundation\Auth\User as Model;

    class User extends Model implements Permissionable
    {
        use HasFactory;
        use HasPermissions;

Затем создадим модель `App\Models\Permission` и унаследуем ее от модели `dmitryrogolev\Can\Models\Permission`.

    <?php

    namespace App\Models;

    use dmitryrogolev\Can\Models\Permission as Model;

    class Permission extends Model
    {

    }

Наконец, добавим метод, возвращающий [полиморфное отношение](https://clck.ru/36JLPn) `многие-ко-многим` разрешений с нашей моделью.

    <?php

    namespace App\Models;

    use dmitryrogolev\Can\Models\Permission as Model;
    use Illuminate\Database\Eloquent\Relations\MorphToMany;
    use App\Models\User;

    class Permission extends Model
    {
        public function users(): MorphToMany
        {
            return $this->permissionables(User::class);
        }
    }

## Использование

### Прикрепление разрешения

Для присоединения одного разрешения или множества разрешений, можно воспользоваться методом `attachPermission`, который принимает идентификатор, slug или модель разрешения, а также их множество. Если разрешение было присоединено, метод вернет `true`.

    $user->attachPermission($id); // bool
    $user->attachPermission('create.users'); // bool
    $user->attachPermission($permission); // bool
    $user->attachPermission([$id, 'create.users', $permission]); // bool

### Отсоединение разрешения

Для отсоединения одного разрешения или множества разрешений, можно воспользоваться методом `detachPermission`, который принимает идентификатор, slug или модель разрешения, а также их множество. Если разрешение было отсоединено, метод вернет `true`.

    $user->detachPermission($id); // bool
    $user->detachPermission('create.users'); // bool
    $user->detachPermission($permission); // bool
    $user->detachPermission([$id, 'create.users', $permission]); // bool

### Отсоединение всех разрешений

Для отсоединения всех разрешений, можно воспользоваться методом `detachAllPermissions`. Также можно воспользоваться методом `detachPermission` с пустым аргументом. Если разрешения были отсоединены, метод вернет `true`.

    $user->detachAllPermissions(); // bool 
    $user->detachPermission(); // bool

### Синхронизация разрешений

Для синхронизации разрешений можно воспользоваться методом `syncPermissions`, который принимает идентификатор, slug или модель разрешения, а также их множество.

    $user->syncPermissions($id); // void
    $user->syncPermissions('create.users'); // void
    $user->syncPermissions($permission); // void
    $user->syncPermissions([$id, 'create.users', $permission]); // void

### Проверка наличия разрешения

Для проверки наличия разрешения у модели, можно воспользоваться методами `hasPermission`, `hasOnePermission` или `can`, которые принимают идентификатор, slug или модель разрешения, а также их множество. Если модель имеет переданное разрешение, метод вернет `true`. 

    if ($user->can('create.users')) {
        // Передаем slug разрешения.
    }

    if ($user->hasPermission($id)) {
        // Передаем идентификатор разрешения.
    }

    if ($user->hasOnePermission($permission)) {
        // Передаем модель разрешения.
    }

Если передать множество разрешений, метод вернет `true` для первого имеющегося у модели разрешения. 

    if ($user->can([$id, 'create.users', $permission])) {
        // У пользователя есть, по крайней мере, одно из переданных разрешений.
    }

    if ($user->hasPermission([$id, 'create.users', $permission])) {
        // У пользователя есть, по крайней мере, одно из переданных разрешений.
    }

    if ($user->hasOnePermission([$id, 'create.users', $permission])) {
        // У пользователя есть, по крайней мере, одно из переданных разрешений.
    }

Для проверки наличия у модели одного разрешения по slug'у доступен магический метод.

    if ($user->canCreateUsers()) {
        // У пользователя есть разрешение со slug'ом "create.users"
    }

    if ($user->canEditUsers()) {
        // У пользователя есть разрешение со slug'ом "edit.users"
    }

### Проверка наличия всех разрешений

Для проверки наличия всех переданных разрешений у модели, можно воспользоваться методом `hasAllPermissions` или методами `hasPermission` или `can`, передав им вторым параметром `true`. Они принимают идентификатор, slug или модель разрешения, а также их множество. Метод возвращает `true` только тогда, когда модель имеет все переданные разрешения. 

    if ($user->can([$id, 'create.users', $permission], true)) {
        // У пользователя есть все переданные разрешения.
    }

    if ($user->hasPermission([$id, 'create.users', $permission], true)) {
        // У пользователя есть все переданные разрешения.
    }

    if ($user->hasAllPermissions([$id, 'create.users', $permission])) {
        // У пользователя есть все переданные разрешения.
    }

### Расширения Blade 

По умолчанию в Blade зарегистрированы помощники проверки наличия разрешений.

Проверка наличия разрешения.

    @can('create.users') // @if(Auth::check() && Auth::user()->hasPermission('create.users'))
        // у пользователя есть разрешение "create.users"
    @endcan

    @permission('create.users') // @if(Auth::check() && Auth::user()->hasPermission('create.users'))
        // у пользователя есть разрешение "create.users"
    @endpermission

Вы можете отключить регистрацию этих директив в конфиге. 

    // config/can.php 
    config(['can.uses.blade' => false]);

    // .env
    CAN_USES_BLADE=false

### Посредники 

По умолчанию зарегистрирован посредник `permission`, проверяющие наличие у модели разрешения.

    Route::get('/', function () {
        //
    })->middleware('permission:create.users');

Вы можете отключить регистрацию этого посредника в конфиге. 

    // config/can.php 
    config(['can.uses.middlewares' => false]);

    // .env
    CAN_USES_MIDDLEWARES=false

## Титры

Данный пакет вдохновлен и разработан на основе [jeremykenedy/laravel-roles](https://github.com/jeremykenedy/laravel-roles).

## Лицензия 

Этот пакет является бесплатным программным обеспечением, распространяемым на условиях [лицензии MIT](./LICENSE).
