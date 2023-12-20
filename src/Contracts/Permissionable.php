<?php

namespace dmitryrogolev\Can\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Функционал разрешений.
 */
interface Permissionable
{
    /**
     * Модель относится к множеству разрешений.
     */
    public function permissions(): MorphToMany;

    /**
     * Возвращает коллекцию разрешений.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    public function getPermissions(): Collection;

    /**
     * Подгружает разрешения.
     */
    public function loadPermissions(): static;

    /**
     * Присоединяет разрешение(-я) к модели.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|\Illuminate\Database\Eloquent\Model|string|int  ...$permission Идентификатор(-ы), slug(-и) или модель(-и) разрешения(-ий).
     * @return bool Было ли присоединено хотябы одно разрешение?
     */
    public function attachPermission(mixed ...$permission): bool;

    /**
     * Отсоединяет разрешение(-я).
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|\Illuminate\Database\Eloquent\Model|string|int  ...$permission Идентификатор(-ы), slug(-и) или модель(-и) разрешения(-ий).
     * @return bool Было ли отсоединено хотябы одно разрешение?
     */
    public function detachPermission(mixed ...$permission): bool;

    /**
     * Отсоединяет все разрешения.
     *
     * @return bool Были ли отсоединены разрешения?
     */
    public function detachAllPermissions(): bool;

    /**
     * Синхронизирует разрешения.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|\Illuminate\Database\Eloquent\Model|string|int  ...$permission Идентификатор(-ы), slug(-и) или модель(-и) разрешения(-ий).
     */
    public function syncPermissions(mixed ...$permissions): void;

    /**
     * Проверяет наличие хотябы одного разрешения из переданных.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|\Illuminate\Database\Eloquent\Model|string|int  ...$permission Идентификатор(-ы), slug(-и) или модель(-и)  разрешения(-ий).
     */
    public function hasOnePermission(mixed ...$permission): bool;

    /**
     * Проверяет наличие всех переданных разрешений.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|\Illuminate\Database\Eloquent\Model|string|int  ...$permission Идентификатор(-ы), slug(-и) или модель(-и) разрешения(-ий).
     */
    public function hasAllPermissions(mixed ...$permission): bool;

    /**
     * Проверяет наличие разрешения(-ий).
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|\Illuminate\Database\Eloquent\Model|string|int  $permission Идентификатор(-ы), slug(-и) или модель(-и) разрешения(-ий).
     * @param  bool  $all Проверить наличие всех разрешений?
     */
    public function hasPermission(mixed $permission, bool $all = false): bool;

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
    public function can(mixed $abilities, mixed $arguments = []);
}
