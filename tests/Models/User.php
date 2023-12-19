<?php

namespace dmitryrogolev\Can\Tests\Models;

use dmitryrogolev\Can\Contracts\Permissionable;
use dmitryrogolev\Can\Tests\Database\Factories\UserFactory;
use dmitryrogolev\Can\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Model;

/**
 * Модель пользователя.
 */
abstract class BaseUser extends Model implements Permissionable
{
    use HasFactory;
    use HasPermissions;

    /**
     * Таблица БД, ассоциированная с моделью.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * Атрибуты, для которых НЕ разрешено массовое присвоение значений.
     *
     * @var array<string>
     */
    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setKeyName(config('can.primary_key'));
        $this->timestamps = config('can.uses.timestamps');
    }

    /**
     * Создайте новый экземпляр фабрики для модели.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }
}

if (config('can.uses.uuid') && config('can.uses.soft_deletes')) {
    class User extends BaseUser
    {
        use HasUuids, SoftDeletes;
    }
} elseif (config('can.uses.uuid')) {
    class User extends BaseUser
    {
        use HasUuids;
    }
} elseif (config('can.uses.soft_deletes')) {
    class User extends BaseUser
    {
        use SoftDeletes;
    }
} else {
    class User extends BaseUser
    {
    }
}
