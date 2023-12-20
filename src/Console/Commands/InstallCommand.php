<?php

namespace dmitryrogolev\Can\Console\Commands;

use Illuminate\Console\Command;

/**
 * Команда установки пакета "Can", предоставляющего функционал разрешений.
 */
class InstallCommand extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'can:install 
                                {--config}
                                {--migrations}
                                {--seeders}';

    /**
     * Описание консольной команды.
     *
     * @var string
     */
    protected $description = 'Installs the "Can" package that provides permission functionality for the Laravel framework.';

    /**
     * Выполнить консольную команду.
     *
     * @return mixed
     */
    public function handle()
    {
        $tag = 'can';

        if ($this->option('config')) {
            $tag .= '-config';
        } elseif ($this->option('migrations')) {
            $tag .= '-migrations';
        } elseif ($this->option('seeders')) {
            $tag .= '-seeders';
        }

        $this->call('vendor:publish', [
            '--tag' => $tag,
        ]);
    }
}
