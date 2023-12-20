<?php

namespace dmitryrogolev\Can\Models;

use dmitryrogolev\Can\Contracts\PermissionHasRelations as ContractPermissionHasRelations;
use dmitryrogolev\Can\Traits\PermissionHasRelations;
use dmitryrogolev\Contracts\Sluggable;
use dmitryrogolev\Traits\HasSlug;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Модель разрешения.
 */
abstract class BasePermission extends Model implements ContractPermissionHasRelations, Sluggable
{
    use HasFactory;
    use HasSlug;
    use PermissionHasRelations;

    /**
     * Атрибуты, для которых разрешено массовое присвоение значений.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setKeyName(config('can.primary_key'));
        $this->timestamps = config('can.uses.timestamps');
        $this->setTable(config('can.tables.permissions'));

        array_push($this->fillable, $this->getSlugName());
    }

    /**
     * Возвращает столбцы, которые содержат уникальные данные.
     *
     * @return array<int, string>
     */
    public function uniqueKeys()
    {
        return [
            $this->getSlugName(),
        ];
    }

    /**
     * Приводит переданную строку к "slug" значению.
     *
     * @param  string  $str Входная строка.
     */
    public static function toSlug(string $str): string
    {
        return Str::slug($str, config('can.separator'));
    }

    /**
     * Создайте новый экземпляр фабрики для модели.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return config('can.factories.permission')::new();
    }
}

if (config('can.uses.uuid') && config('can.uses.soft_deletes')) {
    class Permission extends BasePermission
    {
        use HasUuids, SoftDeletes;
    }
} elseif (config('can.uses.uuid')) {
    class Permission extends BasePermission
    {
        use HasUuids;
    }
} elseif (config('can.uses.soft_deletes')) {
    class Permission extends BasePermission
    {
        use SoftDeletes;
    }
} else {
    class Permission extends BasePermission
    {
    }
}
