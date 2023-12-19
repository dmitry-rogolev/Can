<?php

namespace dmitryrogolev\Can\Tests;

use dmitryrogolev\Can\Contracts\Permissionable;
use dmitryrogolev\Can\Tests\Database\Factories\UserFactory;
use dmitryrogolev\Can\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User;

class BaseUser extends User implements Permissionable
{
    use HasFactory, HasPermissions;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = 'users';
        $this->primaryKey = config('can.primary_key');
        $this->timestamps = config('can.uses.timestamps');
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
