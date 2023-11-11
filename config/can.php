<?php 

/**
 * Конфигурация Can
 * 
 * @version 0.0.1
 * @author Dmitry Rogolev <work.drogolev@internet.ru>
 * @license MIT
 */

return [

    /**
     * * Подключение к БД, которое должен использовать пакет.
     * 
     * Список возможных подключений определен в файле конфигурации "config/database.php".
     * По умолчанию используется подключинение к приложению по умолчанию.
     * 
     * @link https://clck.ru/36LkBo Конфигурирование БД
     */
    'connection' => env('CAN_CONNECTION', config('database.default', null)), 

    /**
     * * Имена таблиц, которые создает пакет. 
     * 
     * Пакет использует полиморфные отношения многие-ко-многим.
     * 
     * Определяются две таблицы: таблица ролей и промежуточная таблица, 
     * которая соединяет модели, использующие трейт HasRoles, с ролями.
     * 
     * @link https://clck.ru/36JLPn Полиморфные отношения многие-ко-многим
     */
    'tables' => [
        'permissions' => env('CAN_TABLES_PERMISSIONS', 'permissions'), 
        'permissionables' => env('CAN_TABLES_PERMISSIONABLES', 'permissionables'), 
    ], 

    /**
     * * Имя полиморфной связи моделей.
     * 
     * Используется в промежуточной таблице для полей {relation_name}_id и {relation_name}_type.
     * Например, permissionable_id и permissionable_type.
     * 
     * В поле {relation_name}_id указывается идентфикатор модели, которая связывается с разрешением.
     * В поле {relation_name}_type указывается полное название модели, 
     * например "\App\Models\User", которая связывается с разрешением.
     * 
     * @link https://clck.ru/36JLPn Полиморфные отношения многие-ко-многим
     */
    'relations' => [
        'permissionable' => env('CAN_RELATIONS_PERMISSIONABLE', 'permissionable'), 
    ], 

    /**
     * * Имя первичного ключа моделей
     * 
     * Первичный ключ - это поле в таблице, которое хранит уникальное значение, 
     * по которому можно явно идентфицировать ту или иную запись в таблице.
     * 
     * @link https://clck.ru/36Ln4n Первичный ключ модели Eloquent
     */
    'primary_key' => env('CAN_PRIMARY_KEY', 'id'), 

    /**
     * * Имена моделей, которые используются в пакете.
     */
    'models' => [

        // Разрешение
        'permission' => env('CAN_MODELS_PERMISSION', \dmitryrogolev\Can\Models\Permission::class),
        
        // Промежуточная модель
        'permissionable' => env('CAN_MODELS_PERMISSIONABLE', \dmitryrogolev\Can\Models\Permissionable::class), 

        // Пользователь по умолчанию
        'user' => env('CAN_MODELS_USER', config('auth.providers.users.model')), 

    ], 

    /**
     * * Имена фабрик, которые используются в пакете.
     */
    'factories' => [

        // Фабрика разрешения
        'permission' => env('CAN_FACTORIES_PERMISSION', \dmitryrogolev\Can\Database\Factories\PermissionFactory::class), 

    ], 

    /**
     * * Имена сидеров, которые использутся в пакете.
     */
    "seeders" => [

        // Сидер разрешения
        "permission" => env('CAN_SEEDERS_PERMISSION', \dmitryrogolev\Can\Database\Seeders\PermissionSeeder::class), 
        
    ], 

    /**
     * * Строковый разделитель. 
     * 
     * Используется для раделения строк на подстроки для поля slug.
     */
    'separator' => env('CAN_SEPARATOR', '.'), 

    /**
     * * Флаги
     */ 
    'uses' => [

        /**
         * * Использовать ли в моделях uuid вместо обычного id.
         * 
         * UUID — это универсальные уникальные буквенно-цифровые идентификаторы длиной 36 символов.
         * 
         * @link https://clck.ru/36JNiT UUID
         */
        'uuid' => env('CAN_USES_UUID', true), 

        /**
         * * Использовать ли программное удаление для моделей. 
         * 
         * Помимо фактического удаления записей из БД, 
         * Eloquent может выполнять «программное удаление» моделей. 
         * При таком удалении, они фактически не удаляются из БД.
         * Вместо этого для каждой модели устанавливается атрибут deleted_at, 
         * указывающий дату и время, когда она была «удалена».
         * 
         * @link https://clck.ru/36JNnr Программное удаление моделей
         */
        'soft_deletes' => env('CAN_USES_SOFT_DELETES', false), 

        /**
         * * Использовать ли временные метки для моделей. 
         * 
         * По умолчанию модели Eloquent определяют поля "created_at" и "updated_at", 
         * в которых хранятся дата и время создания и изменения модели соответственно.
         * 
         * Если вы не хотите, чтобы модели имели временные метки, установите данный флаг в false.
         * 
         * @link https://clck.ru/36JNke Временные метки моделей
         */
        'timestamps' => env('CAN_USES_TIMESTAMPS', true), 

        /**
         * * Использовать ли миграции по умолчанию.
         * 
         * Если вы не публикуете или не создаете свои миграции таблиц для этого пакета, 
         * то установите данный флаг в true.
         */
        'migrations' => env('CAN_USES_MIGRATIONS', false), 

        /**
         * * Использовать ли сидеры по умолчанию.
         * 
         * Если вы хотитите использовать сидеры по умолчанию, установите данный флаг в true.
         */
        'seeders' => env('CAN_USES_SEED', false), 

        /**
         * * Регистрировать ли дериктивы blade (can, endcan, permission, endpermission).
         * 
         * Директивы can и permission предоставляют одинаковый функционал. 
         * 
         * Эти дериктывы применимы только к модели пользователя, 
         * использующего трейт "\dmitryrogolev\Can\Traits\HasPermissions".
         * 
         * @link https://clck.ru/36Ls42 Директивы Blade
         */
        'blade' => env('CAN_USES_BLADE', true), 

        /**
         * * Регистировать ли посредники (can, permission).
         * 
         * Посредники can и permission предоставляют одинаковый функционал.
         * 
         * Эти посредники применимы только к модели пользователя, 
         * использующего трейт "\dmitryrogolev\Can\Traits\HasPermissions".
         * 
         * @link https://clck.ru/36LsKF Посредники
         */
        'middlewares' => env('CAN_USES_MIDDLEWARES', true), 

        /**
         * * Следует ли подгружать отношение модели после изменения. 
         * 
         * По умолчанию после подключения или удаления отношения(-ий) моделей с ролями, 
         * отношения будут подгружены заного. 
         * Это означает, что модель всегда будет хранить актуальные отношения, 
         * однако также это означает увеличение количества запросов к базе данных. 
         * 
         * Если вы делаете много опираций с ролями, 
         * рекомендуется отключить данную функцию для увеличения производительности.
         */
        'load_on_update' => env('CAN_USES_LOAD_ON_UPDATE', true), 

        /**
         * * Следует ли расширять метод "can" интерфейса "Illuminate\Contracts\Auth\Access\Authorizable".
         * 
         * Например, модель "Illuminate\Foundation\Auth\User" реализует данный интерфейс.
         * 
         * Метод can по умолчанию авторизует действие модели. 
         * Трейт HasPermissions расширяет данный метод. 
         * Это означает, что данным методом по прежнему можно будет пользоваться для авторизации действий модели, 
         * но, если передать идентификатор, slug или модель разрешения, то будет вызван метод hasPermission, 
         * проверяющий наличие разрешения у модели.
         * 
         * Если вы не хотите, чтобы данный метод был расширен, установите данный флаг в false.
         * 
         * @link https://clck.ru/36SAPk Авторизация действий с помощью политик
         */
        'extend_can_method' => env('CAN_USES_EXTEND_CAN_METHOD', true), 

    ], 
];
