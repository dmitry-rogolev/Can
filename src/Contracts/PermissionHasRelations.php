<?php

namespace dmitryrogolev\Can\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface PermissionHasRelations
{
    /**
     * Возвращает модели, которые имеют данное разрешение.
     *
     * @param  string  $related Имя модели.
     */
    public function permissionables(string $related): MorphToMany;
}
