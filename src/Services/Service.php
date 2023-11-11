<?php 

namespace dmitryrogolev\Can\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class Service 
{
    /**
     * Возвращает все модели
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    abstract public function index(): Collection;

    /**
     * Возвращает модель по ее идентификатору
     *
     * @param mixed $role
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    abstract public function show(mixed $id): Model|null;

    /**
     * Создает модель
     *
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    abstract public function store(array $attributes = []): Model;

    /**
     * Обновляет модель
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    abstract public function update($model, array $attributes): Model;

    /**
     * Удаляет модель
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool|null
     */
    abstract public function delete($model): bool|null;

    /**
     * Очищает таблицу
     *
     * @return void
     */
    abstract public function truncate(): void;

    /**
     * Удаляет модель
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool|null
     */
    abstract public function forceDelete($model): bool|null;

    /**
     * Востанавливает модель
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    abstract public function restore($model): bool;
}
