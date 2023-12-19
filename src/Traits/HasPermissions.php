<?php

namespace dmitryrogolev\Can\Traits;

if (config('can.uses.extend_can_method')) {
    trait HasPermissions
    {
        use BaseHasPermissions, ExtendCanMethod;
    }
} else {
    trait HasPermissions
    {
        use BaseHasPermissions;
    }
}
