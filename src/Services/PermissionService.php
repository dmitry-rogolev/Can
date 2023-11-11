<?php 

namespace dmitryrogolev\Can\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PermissionService extends Service 
{
    /**
     * Возвращает все разрешения
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index(): Collection 
    {
        return config('can.models.permission')::all();
    }

    /**
     * Возвращает разрешение по его идентификатору, slug'у или модели
     *
     * @param mixed $id
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function show(mixed $id): Model|null 
    {
        return $this->getPermission($id);
    }

    /**
     * Создает разрешение
     *
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function store(array $attributes = []): Model 
    {
        return empty($attributes) ? config('can.models.permission')::factory()->create() : config('can.models.permission')::create($attributes);
    }

    /**
     * Обновляет модель
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model
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
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool|null
     */
    public function delete($model): bool|null
    {
        return $model->delete();
    }

    /**
     * Очищает таблицу ролей
     *
     * @return void
     */
    public function truncate(): void 
    {
        config('can.models.permission')::truncate();
    }

    /**
     * Удаляет модель
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool|null
     */
    public function forceDelete($model): bool|null
    {
        return $model->forceDelete();
    }

    /**
     * Востанавливает модель
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    public function restore($model): bool 
    {
        return $model->restore();
    }

    /**
     * Получить разрешение по его идентификатору или slug'у.
     *
     * @param mixed $role
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getPermission($permission): Model|null 
    {
        if (is_int($permission) || is_string($permission)) {
            return config('can.models.permission')::where(app(config('can.models.permission'))->getKeyName(), $permission)->orWhere('slug', $permission)->first();
        }

        return $permission instanceof (config('can.models.permission')) ? $permission : null;
    }
}
