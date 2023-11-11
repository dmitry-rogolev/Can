<?php 

namespace dmitryrogolev\Can\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * Промежуточная модель полиморфного отношения многие-ко-многим.
 * 
 * @link https://clck.ru/36JLPn Полиморфные отношения многие-ко-многим
 */
class Permissionable extends MorphPivot
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->connection = config('can.connection');
        $this->table = config('can.tables.permissionables');
        $this->timestamps = config('can.uses.timestamps');
    }
}
