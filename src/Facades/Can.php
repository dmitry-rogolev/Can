<?php

namespace dmitryrogolev\Can\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Фасад работы с сервисом разрешений.
 */
class Can extends Facade
{
    /**
     * Возвращает имя компонента.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \dmitryrogolev\Can\Services\PermissionService::class;
    }
}
