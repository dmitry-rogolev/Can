# Can

Функционал разрешений для фреймворка Laravel.

## Содержание

1. [Установка](#установка)
    
    - [Composer](#composer) 
    - [Публикация ресурсов](#публикация-ресурсов)
    - [Добавление функционала в модель](#добавление-функционала-в-модель)
    - [Миграции и сидеры](#миграции-и-сидеры)
    - [Миграции](#миграции)

2. [Применение](#применение)

    - [Создание разрешений](#создание-разрешений)
    - [Прикрепление, отсоединение и синхронизация разрешений](#прикрепление-отсоединение-и-синхронизация-разрешений)
    - [Проверка разрешений](#проверка-разрешений)
    - [Расширения Blade](#расширения-blade)
    - [Посредники](#посредники)

4. [Титры](#титры)
5. [Лицензия](#лицензия)

## Установка 

### Composer

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

#### Публикация всех ресурсов

    php artisan can:install 

#### Публикация ресурсов по отдельности

Конфигурация

    php artisan can:install --config

Миграции

    php artisan can:install --migrations

Сидеры 

    php artisan can:install --seeders

### Добавление функционала в модель

Включите трейт `dmitryrogolev\Can\Traits\HasPermissions` и реализуйте интерфейс `dmitryrogolev\Can\Contracts\Permissionable` в модели.

    <?php

    namespace App\Models;

    // use Illuminate\Contracts\Auth\MustVerifyEmail;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Foundation\Auth\User as Authenticatable;
    use Illuminate\Notifications\Notifiable;
    use Laravel\Sanctum\HasApiTokens;
    use dmitryrogolev\Can\Contracts\Permissionable;
    use dmitryrogolev\Can\Traits\HasPermissions;

    class User extends Authenticatable implements Permissionable 
    {
        use HasApiTokens, 
            HasFactory, 
            Notifiable, 
            HasPermissions;

Трейт `dmitryrogolev\Can\Traits\HasPermissions` добавляет модели возможность работы с разрешениями.

### Миграции 

Создайте таблицы в базе данных

    php artisan migrate

## Применение

### Создание разрешений 

    $canCreateUsers = config('can.models.permission')::create([
        'name' => 'Can Create Users',
        'slug' => 'create.users',
        'description' => '',
        'model' => 'App\Models\User',
    ]);

    $canDeleteUsers = config('can.models.permission')::create([
        'name' => 'Can Delete Users',
        'slug' => 'delete.users',
    ]);

### Прикрепление, отсоединение и синхронизация разрешений 

    $user = config('can.models.user')::find($id);

    $user->attachPermission('view.users'); // Присоединяем разрешение
    $user->detachPermission($canDeleteUsers); // Отсоединяем разрешение
    $user->detachAllPermissions(); // Отсоединяем все разрешения
    $user->syncPermissions(['view.users', 'create.users', 'delete.users']); // Синхронизируем разрешения

### Проверка разрешений

Проверка наличия у пользователя хотябы одного разрешения

    if ($user->can('create.users')) {
        // 
    }

    if ($user->hasPermission([$permission, 24, 56])) {
        // 
    }

    if ($user->hasOnePermission('edit.users,delete.users,23,456')) {
        // 
    }

    if ($user->canDeleteUsers()) {
        // Магический метод
    }

Проверка наличия нескольких разрешений

    if ($user->can(['edit.users', 'delete.users'], true)) {
        // 
    }

    if ($user->hasPermission('edit.users|delete.users|787', true)) {
        // 
    }

    if ($user->hasAllPermissions('edit.users', 567, $permission)) {
        // 
    }

### Расширения Blade 

    @can('delete.users') // @if(Auth::check() && Auth::user()->hasPermission('delete.users'))
        // у пользователя есть разерешение delete.users
    @endcan

    @permission('delete.users') // @if(Auth::check() && Auth::user()->hasPermission('delete.users'))
        // у пользователя есть разерешение delete.users
    @endpermission

### Посредники 

Вы можете защитить роуты

    Route::get('/', function () {
        //
    })->middleware('permission:create.users');

    Route::get('/', function () {
        // can - это синоним permission
    })->middleware('can:create.users');

    Route::get('/', function () {
        //
    })->middleware('permission:create.users', 'can:delete.users'); 

    Route::group(['middleware' => ['can:create.users']], function () {
        //
    });

## Титры

Данный пакет вдохновлен и разработан на основе [jeremykenedy/laravel-roles](https://github.com/jeremykenedy/laravel-roles).

## Лицензия 

Этот пакет является бесплатным программным обеспечением, распространяемым на условиях [лицензии MIT](./LICENSE).