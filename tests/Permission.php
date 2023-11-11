<?php 

namespace dmitryrogolev\Can\Tests;

use dmitryrogolev\Can\Models\Permission as Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Permission extends Model 
{
    /**
     * Разрешение относится к множеству пользователей
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users(): MorphToMany 
    {
        return $this->permissionables(config('can.models.user'));
    }
}
