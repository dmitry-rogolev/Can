<?php

namespace dmitryrogolev\Can\Tests;

use dmitryrogolev\Can\Providers\CanServiceProvider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    /**
     * Количество выполненных запросов к БД.
     */
    protected int $queryExecutedCount = 0;

    /**
     * SQL-запросы, отправленные на выполнение.
     *
     * @var array<int, string>
     */
    protected array $queries;

    public function setUp(): void
    {
        parent::setUp();

        $this->registerListeners();
    }

    /**
     * Получить поставщиков пакета
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            CanServiceProvider::class,
        ];
    }

    /**
     * Определите настройку маршрутов.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    protected function defineRoutes($router)
    {
        $router->middleware('web')->group(__DIR__.'/routes/web.php');
    }

    /**
     * Возвращает сгенерированную с помощью фабрики модель.
     */
    protected function generate(string $class, array|int|bool|null $count = null, array|bool $state = [], bool $create = true): Model|Collection
    {
        if (is_bool($state)) {
            $create = $state;
            $state = [];
        }

        if (is_array($count)) {
            $state = $count;
            $count = null;
        }

        if (is_bool($count)) {
            $create = $count;
            $count = null;
        }

        $factory = $class::factory($count, $state);

        return $create ? $factory->create() : $factory->make();
    }

    /**
     * Зарегистрировать слушатели событий.
     */
    protected function registerListeners(): void
    {
        DB::listen(function ($query) {
            $this->queryExecutedCount++;
            $this->queries[] = $query->sql;
        });
    }

    /**
     * Сбросить количество выполненных запросов к БД.
     */
    protected function resetQueryExecutedCount(): void
    {
        $this->queryExecutedCount = 0;
    }

    /**
     * Сбрасывает список SQL-запросов, отправленных на выполнение.
     */
    protected function resetQueries(): void
    {
        $this->queries = [];
    }

    /**
     * Подтвердить количество выполненных запросов к БД.
     */
    protected function assertQueryExecutedCount(int $expectedCount, ?string $message = ''): void
    {
        $this->assertEquals($expectedCount, $this->queryExecutedCount, $message);
    }
}
