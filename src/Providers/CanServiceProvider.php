<?php 

namespace dmitryrogolev\Can\Providers;

use dmitryrogolev\Can\Console\Commands\InstallCommand;
use dmitryrogolev\Can\Http\Middlewares\VerifyPermission;
use Illuminate\Support\ServiceProvider;

class CanServiceProvider extends ServiceProvider
{
    /**
     * Имя тега пакета
     *
     * @var string
     */
    private string $packageTag = 'can';

    /**
     * Регистрация любых служб пакета.
     * 
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfig();
        $this->loadMigrations();
        $this->publishFiles();
        $this->registerCommands();
    }

    /**
     * Загрузка любых служб пакета.
     */
    public function boot(): void
    {
        if (config('can.uses.middlewares')) {
            $this->app['router']->aliasMiddleware('can', VerifyPermission::class);
            $this->app['router']->aliasMiddleware('permission', VerifyPermission::class);
        }
    }

    /**
     * Объединяем конфигурацию пакета с конфигурацией приложения
     *
     * @return void
     */
    private function mergeConfig(): void 
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/can.php', 'can');
    }

    /**
     * Регистируем миграции пакета.
     *
     * @return void
     */
    private function loadMigrations(): void 
    {
        if (config('can.uses.migrations')) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        }
    }

    /**
     * Публикуем файлы пакета
     *
     * @return void
     */
    private function publishFiles(): void 
    {
        $this->publishes([
            __DIR__.'/../../config/can.php' => config_path('can.php'), 
        ], $this->packageTag.'-config');

        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'), 
        ], $this->packageTag.'-migrations');

        $this->publishes([
            __DIR__.'/../../database/seeders/publish' => database_path('seeders'), 
        ], $this->packageTag.'-seeders');

        $this->publishes([
            __DIR__.'/../../config/can.php' => config_path('can.php'), 
            __DIR__.'/../../database/migrations' => database_path('migrations'), 
            __DIR__.'/../../database/seeders/publish' => database_path('seeders'), 
        ], $this->packageTag);
    }

    /**
     * Регистрируем сидеры
     *
     * @return void
     */
    private function loadSeedsFrom(): void 
    {
        if (config('can.uses.seeders')) {
            $this->app->afterResolving('seed.handler', function ($handler) {
                $handler->register(config('can.seeders.permission'));
            });
        }
    }

    /**
     * Регистрируем директивы Blade
     *
     * @return void
     */
    private function registerBladeExtensions(): void 
    {
        if (config('can.uses.blade')) {
            $blade = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();

            $blade->directive('can', function ($expression) {
                return "<?php if (Auth::check() && Auth::user()->hasPermission({$expression})): ?>";
            });
            $blade->directive('endcan', function () {
                return '<?php endif; ?>';
            });

            $blade->directive('permission', function ($expression) {
                return "<?php if (Auth::check() && Auth::user()->hasPermission({$expression})): ?>";
            });
            $blade->directive('endpermission', function () {
                return '<?php endif; ?>';
            });
        }
    }

    /**
     * Регистрируем комманды 
     *
     * @return void
     */
    private function registerCommands(): void 
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class, 
            ]);
        }
    }
}
