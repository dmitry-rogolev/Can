<?php

namespace dmitryrogolev\Can\Models;

use dmitryrogolev\Can\Contracts\PermissionHasRelations as ContractPermissionHasRelations;
use dmitryrogolev\Can\Traits\PermissionHasRelations;
use dmitryrogolev\Can\Traits\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasePermission extends Model implements ContractPermissionHasRelations
{
    use HasFactory, PermissionHasRelations, Sluggable;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'model',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->connection = config('can.connection');
        $this->table = config('can.tables.permissions');
        $this->primaryKey = config('can.primary_key');
        $this->timestamps = config('can.uses.timestamps');
    }

    /**
     * Возвращаем разрешение по его slug
     *
     * Например, Permission::canCreateUser(),
     * Permission::canDeletePermission(), Permission::canUpdateUser()
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        try {
            return parent::__callStatic($method, $parameters);
        } catch (\BadMethodCallException $e) {
            if ($permission = static::findBySlug(str($method)->after('can')->toString())) {
                return $permission;
            }

            throw $e;
        }
    }

    protected static function newFactory()
    {
        return config('can.factories.permission')::new();
    }
}
