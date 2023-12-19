<?php

namespace dmitryrogolev\Can\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait PermissionHasRelations
{
    /**
     * Возвращает модели, которые имеют данное разрешение
     *
     * @param  string  $related Имя модели
     */
    public function permissionables(string $related): MorphToMany
    {
        $query = $this->morphedByMany($related, config('can.relations.permissionable'))->using(config('can.models.permissionable'));

        return config('can.uses.timestamps') ? $query->withTimestamps() : $query;
    }
}
