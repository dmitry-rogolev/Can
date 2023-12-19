<?php

namespace dmitryrogolev\Can\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    /**
     * Создаем фабрику и указываем имя модели.
     *
     * @param  mixed  ...$parameters
     */
    public function __construct(...$parameters)
    {
        parent::__construct(...$parameters);
        $this->model = config('can.models.permission');
    }

    /**
     * Устанавливает состояние модели по умолчанию.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->name();

        return [
            'name' => $name,
            'slug' => $name,
            'description' => $name.' permission',
        ];
    }
}
