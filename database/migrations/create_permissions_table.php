<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица разрешений
 */
return new class extends Migration
{
    /**
     * Запустить миграцию
     */
    public function up(): void
    {
        $table = config('can.tables.permissions');
        $connection = config('can.connection');

        if (! Schema::connection($connection)->hasTable($table)) {
            Schema::connection($connection)->create($table, function (Blueprint $table) {
                if (config('can.uses.uuid')) {
                    $table->uuid(config('can.primary_key'));
                } else {
                    $table->id();
                }

                $table->string('name', 255)->unique();
                $table->string('slug', 255)->unique();
                $table->text('description')->nullable();
                $table->string('model', 255)->nullable();

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
     * Откатить миграцию
     */
    public function down(): void
    {
        Schema::connection(config('can.connection'))->dropIfExists(config('can.tables.permissions'));
    }
};
