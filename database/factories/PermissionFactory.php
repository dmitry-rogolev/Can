<?php

namespace dmitryrogolev\Can\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика разрешения.
 */
class PermissionFactory extends Factory
{
    /**
     * Создаем фабрику и указываем имя модели.
     */
    public function __construct(mixed ...$parameters)
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
        $name = fake()->unique()->word();
        $slugName = app($this->model)->getSlugName();

        return [
            'name' => ucfirst($name),
            $slugName => $this->model::toSlug($name),
            'description' => ucfirst($name).' permission',
        ];
    }
}
