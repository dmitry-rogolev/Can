<?php

namespace dmitryrogolev\Can\Traits;

use BadMethodCallException;
use dmitryrogolev\Can\Facades\Can;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Функционал разрешений.
 */
trait HasPermissions
{
    /**
     * Модель относится к множеству разрешений.
     */
    public function permissions(): MorphToMany
    {
        $query = $this->morphToMany(config('can.models.permission'), config('can.relations.permissionable'))->using(config('can.models.permissionable'));

        return config('can.uses.timestamps') ? $query->withTimestamps() : $query;
    }

    /**
     * Возвращает коллекцию разрешений.
     * 
     * @return \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    /**
     * Подгружает разрешения.
     */
    public function loadPermissions(): static
    {
        return $this->load('permissions');
    }

    /**
     * Присоединяет разрешение(-я) к модели.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|\Illuminate\Database\Eloquent\Model|string|int  ...$permission Идентификатор(-ы), slug(-и) или модель(-и) разрешения(-ий).
     * @return bool Было ли присоединено хотябы одно разрешение?
     */
    public function attachPermission(mixed ...$permission): bool
    {
        // Получаем модели разрешений из переданных параметров.
        // При этом переданные идентификаторы и slug'и будут заменены на модели.
        //
        // Затем фильтруем разрешения, оставляя те, которые еще не были присоединены к модели.
        //
        // Наконец, заменяем модели на их идентификаторы,
        // так как метод attach ожидает массив идентификаторов.
        $permissions = $this->getModelsForAttach($permission);

        if (empty($permissions)) {
            return false;
        }

        // Присоединяем разрешения.
        $this->permissions()->attach($permissions);

        // Обновляем разрешения, если данная опция включена.
        if (config('can.uses.load_on_update')) {
            $this->loadPermissions();
        }

        return true;
    }

    /**
     * Отсоединяет разрешение(-я).
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|\Illuminate\Database\Eloquent\Model|string|int  ...$permission Идентификатор(-ы), slug(-и) или модель(-и) разрешения(-ий).
     * @return bool Было ли отсоединено хотябы одно разрешение?
     */
    public function detachPermission(mixed ...$permission): bool
    {
        $permissions = $this->toFlattenArray($permission);

        // Если ничего не передано, отсоединяем все разрешения.
        if (empty($permissions)) {
            return $this->detachAllPermissions();
        }

        // Получаем модели разрешений из переданных параметров.
        // При этом переданные идентификаторы и slug'и будут заменены на модели.
        //
        // Затем фильтруем разрешения, оставляя те, которые фактически присоединены к модели.
        //
        // Наконец, заменяем модели на их идентификаторы,
        // так как метод detach ожидает массив идентификаторов.
        $permissions = $this->getModelsForDetach($permissions);

        if (empty($permissions)) {
            return false;
        }

        // Отсоединяем разрешения.
        $this->permissions()->detach($permissions);

        // Обновляем разрешения, если данная опция включена.
        if (config('can.uses.load_on_update')) {
            $this->loadPermissions();
        }

        return true;
    }

    /**
     * Отсоединяет все разрешения.
     *
     * @return bool Были ли отсоединены разрешения?
     */
    public function detachAllPermissions(): bool
    {
        if ($this->permissions->isEmpty()) {
            return false;
        }

        // Отсоединяем все разрешения.
        $this->permissions()->detach();

        // Обновляем разрешения, если данная опция включена.
        if (config('can.uses.load_on_update')) {
            $this->loadPermissions();
        }

        return true;
    }

    /**
     * Синхронизирует разрешения.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|\Illuminate\Database\Eloquent\Model|string|int  ...$permission Идентификатор(-ы), slug(-и) или модель(-и) разрешения(-ий).
     */
    public function syncPermissions(mixed ...$permissions): void
    {
        $this->detachAllPermissions();
        $this->attachPermission($permissions);
    }

    /**
     * Проверяет наличие хотябы одного разрешения из переданных.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|\Illuminate\Database\Eloquent\Model|string|int  ...$permission Идентификатор(-ы), slug(-и) или модель(-и) разрешения(-ий).
     */
    public function hasOnePermission(mixed ...$permission): bool
    {
        // Получаем модели разрешений из переданных параметров.
        // При этом переданные идентификаторы и slug'и будут заменены на модели.
        $permissions = $this->parsePermissions($permission);

        // Возвращаем true, если хотябы одно разрешение присоединено.
        foreach ($permissions as $permission) {
            if ($this->checkPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверяет наличие всех переданных разрешений.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|\Illuminate\Database\Eloquent\Model|string|int  ...$permission Идентификатор(-ы), slug(-и) или модель(-и) разрешения(-ий).
     */
    public function hasAllPermissions(mixed ...$permission): bool
    {
        // Получаем модели разрешений из переданных параметров.
        // При этом переданные идентификаторы и slug'и будут заменены на модели.
        $permissions = $this->parsePermissions($permission);

        if (empty($permissions)) {
            return false;
        }

        // Возвращаем false, если хотябы одно разрешение не присоединено.
        foreach ($permissions as $permission) {
            if (! $this->checkPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Проверяет наличие разрешения(-ий).
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|\Illuminate\Database\Eloquent\Model|string|int  $permission Идентификатор(-ы), slug(-и) или модель(-и) разрешения(-ий).
     * @param  bool  $all Проверить наличие всех разрешений?
     */
    public function hasPermission(mixed $permission, bool $all = false): bool
    {
        return $all ? $this->hasAllPermissions($permission) : $this->hasOnePermission($permission);
    }

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
    public function can(mixed $abilities, mixed $arguments = [])
    {
        if (config('can.uses.extend_can_method') && $permissions = $this->parsePermissions($abilities) && ! empty($permissions)) {
            $all = is_bool($arguments) ? $arguments : false;

            return $this->hasPermission($permissions, $all);
        }

        if ($this instanceof Authorizable) {
            return parent::can($abilities, $arguments);
        }

        return false;
    }

    public function __call($method, $parameters)
    {
        try {
            return parent::__call($method, $parameters);
        } catch (BadMethodCallException $e) {
            if (is_bool($can = $this->callMagicCanPermission($method))) {
                return $can;
            }

            throw $e;
        }
    }

    /**
     * Магический метод. Проверяет наличие разрешения по его slug'у.
     *
     * Пример вызова: canCreateUsers(), canUpdatePermissions().
     */
    protected function callMagicCanPermission(string $method): ?bool
    {
        if (str_starts_with($method, 'can')) {
            $slug = str($method)->after('can')->snake(config('can.separator'))->toString();

            return $this->hasOnePermission($slug);
        }

        return null;
    }

    /**
     * Проверяет наличие разрешения у модели.
     */
    protected function checkPermission(Model $permission): bool
    {
        return $this->roles->contains(fn ($item) => $item->is($permission));
    }

    /**
     * Проверяет, является ли переданное значение идентификатором.
     */
    protected function isId(mixed $value): bool
    {
        return is_int($value) || is_string($value) && (Str::isUuid($value) || Str::isUlid($value));
    }

    /**
     * Приводит переданное значение к выравненному массиву.
     *
     * @return array<int, mixed>
     */
    protected function toFlattenArray(mixed $value): array
    {
        return Arr::flatten([$value]);
    }

    /**
     * Заменяет идентификаторы и slug'и на модели.
     *
     * @param  array<int, mixed>  $permissions
     * @return array<int, \Illuminate\Database\Eloquent\Model>
     */
    protected function replaceIdsWithModels(array $permissions): array
    {
        // Сортируем переданный массив. Складываем модели в один массив,
        // а идентификаторы и slug'и в другой массив.
        [$result, $ids] = $this->sortModelsAndIds($permissions);

        // Если были переданы идентификаторы и(или) slug'и, получаем по ним модели,
        // а затем добавляем их в результирующий массив.
        if (! empty($ids)) {
            $models = Can::whereUniqueKey($ids);
            $result = array_merge($result, $models->all());
        }

        return $result;
    }

    /**
     * Сортируем переданный массив на модели ролей и на идентификаторы и slug'и.
     *
     * @param  array<int, mixed>  $permissions
     * @return array<int, array<int, mixed>>
     */
    protected function sortModelsAndIds(array $permissions): array
    {
        $ids = [];
        $models = [];

        foreach ($permissions as $permission) {
            if (is_a($permission, config('can.models.permission')) && $permission->exists) {
                $models[] = $permission;
            } elseif ($this->isId($permission) || is_string($permission)) {
                $ids[] = $permission;
            }
        }

        return [$models, $ids];
    }

    /**
     * Возвращает модели ролей из переданных значений.
     *
     * @param  array<int, mixed>  $permissions
     * @return array<int, \Illuminate\Database\Eloquent\Model>
     */
    protected function parsePermissions(mixed $permissions): array
    {
        return $this->replaceIdsWithModels(
            $this->toFlattenArray($permissions)
        );
    }

    /**
     * Возвращает только те разрешения, которых нет у модели.
     *
     * @param  array<int, \Illuminate\Database\Eloquent\Model>  $permissions
     * @return array<int, \Illuminate\Database\Eloquent\Model>
     */
    protected function notAttachedFilter(array $permissions): array
    {
        return array_values(array_filter($permissions, fn ($permission) => ! $this->checkPermission($permission)));
    }

    /**
     * Возвращает только те разрешения, которые присоединены к модели.
     *
     * @param  array<int, \Illuminate\Database\Eloquent\Model>  $permissions
     * @return array<int, \Illuminate\Database\Eloquent\Model>
     */
    protected function attachedFilter(array $permissions): array
    {
        return array_values(array_filter(
            $permissions,
            fn ($permission) => $this->permissions->contains(fn ($item) => $item->is($permission))
        ));
    }

    /**
     * Заменяет модели на их идентификаторы.
     *
     * @param  array<int, \Illuminate\Database\Eloquent\Model>  $permissions
     * @return array<int, mixed>
     */
    protected function modelsToIds(array $permissions): array
    {
        return collect($permissions)->pluck($this->getKeyName())->all();
    }

    /**
     * Возвращает модели разрешений, которые могут быть присоединены к модели.
     *
     * @param  array<int, mixed>  $permissions
     * @return array<int, mixed>
     */
    protected function getModelsForAttach(array $permissions): array
    {
        return $this->modelsToIds(
            $this->notAttachedFilter(
                $this->parsePermissions($permissions)
            )
        );
    }

    /**
     * Возвращает модели разрешений, которые могут быть отсоединены от модели.
     *
     * @param  array<int, mixed>  $permissions
     * @return array<int, mixed>
     */
    protected function getModelsForDetach(array $permissions): array
    {
        return $this->modelsToIds(
            $this->attachedFilter(
                $this->parseRoles($permissions)
            )
        );
    }
}
