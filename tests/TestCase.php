<?php 

namespace dmitryrogolev\Can\Tests;

use dmitryrogolev\Can\Providers\CanServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase 
{
    use RefreshDatabase;

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
}
