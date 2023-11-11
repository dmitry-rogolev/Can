<?php 

namespace dmitryrogolev\Can\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface PermissionHasRelations 
{
    /**
     * Возвращает модели, которые имеют это разрешение
     *
     * @param string $related Имя модели
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function permissionables(string $related): MorphToMany;
}
