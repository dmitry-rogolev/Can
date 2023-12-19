<?php

namespace dmitryrogolev\Can\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class Service
{
    /**
     * Возвращает все модели
     */
    abstract public function index(): Collection;

    /**
     * Возвращает модель по ее идентификатору
     *
     * @param  mixed  $role
     */
    abstract public function show(mixed $id): ?Model;

    /**
     * Создает модель
     */
    abstract public function store(array $attributes = []): Model;

    /**
     * Обновляет модель
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    abstract public function update($model, array $attributes): Model;

    /**
     * Удаляет модель
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    abstract public function delete($model): ?bool;

    /**
     * Очищает таблицу
     */
    abstract public function truncate(): void;

    /**
     * Удаляет модель
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    abstract public function forceDelete($model): ?bool;

    /**
     * Востанавливает модель
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    abstract public function restore($model): bool;
}
