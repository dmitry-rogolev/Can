<?php

namespace dmitryrogolev\Can\Traits;

use dmitryrogolev\Can\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

trait BaseHasPermissions
{
    /**
     * Модель относится к множеству разрешений
     */
    public function permissions(): MorphToMany
    {
        $query = $this->morphToMany(config('can.models.permission'), config('can.relations.permissionable'))->using(config('can.models.permissionable'));

        return config('can.uses.timestamps') ? $query->withTimestamps() : $query;
    }

    /**
     * Подгружает разрешения
     */
    public function loadPermissions(): static
    {
        return $this->load('permissions');
    }

    /**
     * Присоединить разрешения
     *
     * Можно передавать идентификатор, slug или модель разрешения.
     *
     * @param  mixed  ...$permission
     */
    public function attachPermission(...$permission): bool
    {
        $permissions = Helper::arrayFrom($permission);
        $attached = false;

        foreach ($permissions as $permission) {
            if (! $this->checkPermission($permission) && $model = $this->getPermission($permission)) {
                $this->permissions()->attach($model);
                $attached = true;
            }
        }

        if (config('can.uses.load_on_update') && $attached) {
            $this->loadPermissions();
        }

        return $attached;
    }

    /**
     * Отсоединить разрешения
     *
     * Можно передавать идентификатор, slug или модель разрешения.
     * Если ничего не передовать, то будут отсоединены все отношения.
     *
     * @param  mixed  ...$permission
     */
    public function detachPermission(...$permission): bool
    {
        $permissions = Helper::arrayFrom($permission);
        $detached = false;

        if (empty($permissions)) {
            return $this->detachAllPermissions();
        }

        foreach ($permissions as $permission) {
            if ($this->checkPermission($permission) && $model = $this->getPermission($permission)) {
                $this->permissions()->detach($model);
                $detached = true;
            }
        }

        if (config('can.uses.load_on_update') && $detached) {
            $this->loadPermissions();
        }

        return $detached;
    }

    /**
     * Отсоединить все разрешения
     */
    public function detachAllPermissions(): bool
    {
        if ($this->permissions->isNotEmpty()) {
            $this->permissions()->detach();

            if (config('can.uses.load_on_update')) {
                $this->loadPermissions();
            }

            return true;
        }

        return false;
    }

    /**
     * Синхронизирует разрешения
     *
     * @param  mixed  ...$permissions
     */
    public function syncPermissions(...$permissions): void
    {
        $this->detachAllPermissions();
        $this->attachPermission($permissions);
    }

    /**
     * Проверяем наличие хотябы одного разрешения
     *
     * @param  array  ...$permission
     */
    public function hasOnePermission(...$permission): bool
    {
        $permissions = Helper::arrayFrom($permission);

        foreach ($permissions as $permission) {
            if ($this->checkPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверяем наличие всех разрешений
     *
     * @param  array  ...$permission
     */
    public function hasAllPermissions(...$permission): bool
    {
        $permissions = Helper::arrayFrom($permission);

        foreach ($permissions as $permission) {
            if (! $this->checkPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Проверяем наличие хотябы одного разрешения.
     *
     * Если передать второй параметр, проверяет наличие всех разрешений.
     */
    public function hasPermission(mixed $permission, bool $all = false): bool
    {
        return $all ? $this->hasAllPermissions($permission) : $this->hasOnePermission($permission);
    }

    /**
     * Проверяем наличие разрешения
     *
     * Например, canCreateUsers(), canUpdatePermissions()
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        try {
            return parent::__call($method, $parameters);
        } catch (\BadMethodCallException $e) {
            if (is_bool($can = $this->callMagicCanPermission($method))) {
                return $can;
            }

            throw $e;
        }
    }

    /**
     * Проверяем наличие разрешения
     *
     * Например, canCreateUsers(), canUpdatePermissions()
     *
     * @param  string  $method
     * @param  array  $parameters
     */
    protected function callMagicCanPermission($method): ?bool
    {
        if (str_starts_with($method, 'can')) {
            return $this->hasPermission(Helper::slug(Str::after($method, 'can')));
        }

        return null;
    }

    /**
     * Проверяем наличие разрешения
     *
     * @param  mixed  $role
     */
    protected function checkPermission(mixed $permission): bool
    {
        return $this->permissions->contains(fn ($item) => $item->getKey() == $permission || $item->slug == $permission || $permission instanceof (config('can.models.permission')) && $item->is($permission));
    }

    /**
     * Получить разрешение по его идентификатору или slug'у.
     *
     * @param  mixed  $role
     */
    protected function getPermission($permission): ?Model
    {
        if (is_int($permission) || is_string($permission)) {
            return config('can.models.permission')::where(app(config('can.models.permission'))->getKeyName(), $permission)->orWhere('slug', $permission)->first();
        }

        return $permission instanceof (config('can.models.permission')) ? $permission : null;
    }
}
