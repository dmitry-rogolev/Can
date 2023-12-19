<?php

namespace dmitryrogolev\Can\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface Permissionable
{
    /**
     * Модель относится к множеству разрешений
     */
    public function permissions(): MorphToMany;

    /**
     * Подгружает разрешения
     */
    public function loadPermissions(): static;

    /**
     * Присоединить разрешения
     *
     * Можно передавать идентификатор, slug или модель разрешения.
     *
     * @param  mixed  ...$permission
     */
    public function attachPermission(...$permission): bool;

    /**
     * Отсоединить разрешения
     *
     * Можно передавать идентификатор, slug или модель разрешения.
     * Если ничего не передовать, то будут отсоединены все отношения.
     *
     * @param  mixed  ...$permission
     */
    public function detachPermission(...$permission): bool;

    /**
     * Отсоединить все разрешения
     */
    public function detachAllPermissions(): bool;

    /**
     * Синхронизирует разрешения
     *
     * @param  mixed  ...$permissions
     */
    public function syncPermissions(...$permissions): void;

    /**
     * Проверяем наличие хотябы одного разрешения
     *
     * @param  array  ...$permission
     */
    public function hasOnePermission(...$permission): bool;

    /**
     * Проверяем наличие всех разрешений
     *
     * @param  array  ...$permission
     */
    public function hasAllPermissions(...$permission): bool;

    /**
     * Проверяем наличие хотябы одного разрешения.
     *
     * Если передать второй параметр, проверяет наличие всех разрешений.
     */
    public function hasPermission(mixed $permission, bool $all = false): bool;
}
