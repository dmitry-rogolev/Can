<?php

namespace dmitryrogolev\Can\Models;

use dmitryrogolev\Can\Models\BasePermission as Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель роли
 */
if (config('can.uses.uuid') && config('can.uses.soft_deletes')) {
    class Permission extends Model
    {
        use HasUuids, SoftDeletes;
    }
} else if (config('can.uses.uuid')) {
    class Permission extends Model
    {
        use HasUuids;
    }
} else if (config('can.uses.soft_deletes')) {
    class Permission extends Model
    {
        use SoftDeletes;
    }
} else {
    class Permission extends Model
    {
        
    }
}
