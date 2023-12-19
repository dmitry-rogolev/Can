<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица разрешений.
 */
return new class extends Migration
{
    /**
     * Имя таблицы.
     */
    protected string $table;

    /**
     * Имя первичного ключа.
     */
    protected string $keyName;

    /**
     * Имя slug'а.
     */
    protected string $slugName;

    public function __construct()
    {
        $this->table = config('can.tables.permissions');
        $this->keyName = config('can.primary_key');
        $this->slugName = app(config('can.models.permission'))->getSlugName();
    }

    /**
     * Запустить миграцию.
     */
    public function up(): void
    {
        $exists = Schema::hasTable($this->table);

        if (! $exists) {
            Schema::create($this->table, function (Blueprint $table) {
                config('can.uses.uuid') ? $table->uuid($this->keyName) : $table->id($this->keyName);
                $table->string('name', 255);
                $table->string($this->slugName, 255)->unique();
                $table->text('description')->nullable();

                if (config('can.uses.timestamps')) {
                    $table->timestamps();
                }

                if (config('can.uses.soft_deletes')) {
                    $table->softDeletes();
                }
            });
        }
    }

    /**
     * Откатить миграцию.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};
