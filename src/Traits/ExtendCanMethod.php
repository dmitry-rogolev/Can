<?php

namespace dmitryrogolev\Can\Traits;

use Illuminate\Contracts\Auth\Access\Authorizable;

trait ExtendCanMethod
{
    /**
     * Определите, обладает ли объект данной способностью.
     *
     * Если передать идентификатор, slug или модель разрешения, то будет вызван метод hasPermission,
     * проверяющий наличие разрешения у модели.
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function can($abilities, $arguments = [])
    {
        if ($permission = $this->getPermission($abilities)) {
            return $this->hasPermission($permission, is_bool($arguments) ? $arguments : false);
        }

        if ($this instanceof Authorizable) {
            return parent::can($abilities, $arguments);
        }

        return false;
    }
}
