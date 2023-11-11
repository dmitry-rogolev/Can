<?php 

namespace dmitryrogolev\Can\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface Permissionable
{
    /**
     * Модель относится к множеству разрешений
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function permissions(): MorphToMany;

    /**
     * Подгружает разрешения
     * 
     * @return static
     */
    public function loadPermissions(): static;

    /**
     * Присоединить разрешения
     * 
     * Можно передавать идентификатор, slug или модель разрешения.
     * 
     * @param mixed ...$permission
     * @return bool
     */
    public function attachPermission(...$permission): bool;

    /**
     * Отсоединить разрешения
     * 
     * Можно передавать идентификатор, slug или модель разрешения.
     * Если ничего не передовать, то будут отсоединены все отношения.
     * 
     * @param mixed ...$permission
     * @return bool
     */
    public function detachPermission(...$permission): bool;

    /**
     * Отсоединить все разрешения 
     *
     * @return boolean
     */
    public function detachAllPermissions(): bool;

    /**
     * Синхронизирует разрешения
     *
     * @param mixed ...$permissions
     * @return void
     */
    public function syncPermissions(...$permissions): void;

    /**
     * Проверяем наличие хотябы одного разрешения
     *
     * @param array ...$permission
     * @return boolean
     */
    public function hasOnePermission(...$permission): bool;

    /**
     * Проверяем наличие всех разрешений
     *
     * @param array ...$permission
     * @return boolean
     */
    public function hasAllPermissions(...$permission): bool;

    /**
     * Проверяем наличие хотябы одного разрешения. 
     * 
     * Если передать второй параметр, проверяет наличие всех разрешений.
     *
     * @param mixed $permission
     * @param boolean $all
     * @return boolean
     */
    public function hasPermission(mixed $permission, bool $all = false): bool;
}
