<?php

namespace dmitryrogolev\Can\Services;

use dmitryrogolev\Contracts\Resourcable as ResourcableContract;
use dmitryrogolev\Services\Service;
use dmitryrogolev\Traits\Resourcable;

/**
 * Сервис работы с таблицей разрешений.
 */
class PermissionService extends Service implements ResourcableContract
{
    use Resourcable;

    public function __construct()
    {
        parent::__construct();

        $this->setModel(config('can.models.permission'));
        $this->setSeeder(config('can.seeders.permission'));
    }
}
