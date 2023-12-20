<?php

namespace dmitryrogolev\Can\Tests\Feature\Console\Commands;

use dmitryrogolev\Can\Tests\TestCase;

/**
 * Тестируем команду установки пакета "Can".
 */
class InstallCommandTest extends TestCase
{
    /**
     * Запускается ли команда?
     */
    public function test_run(): void
    {
        $this->artisan('can:install')->assertOk();
        $this->artisan('can:install --config')->assertOk();
        $this->artisan('can:install --migrations')->assertOk();
        $this->artisan('can:install --seeders')->assertOk();
    }
}
