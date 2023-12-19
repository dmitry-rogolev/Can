<?php

namespace dmitryrogolev\Can\Tests;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

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
