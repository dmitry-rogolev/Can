<?php

namespace dmitryrogolev\Can\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PermissionService extends Service
{
    /**
     * Возвращает все разрешения
     */
    public function index(): Collection
    {
        return config('can.models.permission')::all();
    }

    /**
     * Возвращает разрешение по его идентификатору, slug'у или модели
     */
    public function show(mixed $id): ?Model
    {
        return $this->getPermission($id);
    }

    /**
     * Создает разрешение
     */
    public function store(array $attributes = []): Model
    {
        return empty($attributes) ? config('can.models.permission')::factory()->create() : config('can.models.permission')::create($attributes);
    }

    /**
     * Обновляет модель
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function update($model, array $attributes): Model
    {
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    /**
     * Удаляет модель
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function delete($model): ?bool
    {
        return $model->delete();
    }

    /**
     * Очищает таблицу ролей
     */
    public function truncate(): void
    {
        config('can.models.permission')::truncate();
    }

    /**
     * Удаляет модель
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function forceDelete($model): ?bool
    {
        return $model->forceDelete();
    }

    /**
     * Востанавливает модель
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function restore($model): bool
    {
        return $model->restore();
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
