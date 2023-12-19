<?php

namespace dmitryrogolev\Can\Tests\Database\Factories;

use Orchestra\Testbench\Factories\UserFactory as TestbenchUserFactory;

/**
 * Фабрика модели пользователя.
 */
class UserFactory extends TestbenchUserFactory
{
    public function __construct(...$parameters)
    {
        parent::__construct(...$parameters);

        $this->model = config('can.models.user');
    }
}
