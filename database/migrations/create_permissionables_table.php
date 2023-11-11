<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Промежуточная таблица полиморфного отношения многие-ко-многим.
 * 
 * @link https://clck.ru/36JLPn Полиморфные отношения многие-ко-многим
 */
return new class extends Migration
{
    /**
     * Запустить миграцию
     */
    public function up(): void
    {
        $table = config('can.tables.permissionables');
        $connection = config('can.connection');

        if (! Schema::connection($connection)->hasTable($table)) {
            Schema::connection($connection)->create($table, function (Blueprint $table) {
                $table->foreignIdFor(config('can.models.permission'));

                if (config('can.uses.uuid')) {
                    $table->uuidMorphs(config('can.relations.permissionable'));
                } else {
                    $table->morphs(config('can.relations.permissionable'));
                }

                if (config('can.uses.timestamps')) {
                    $table->timestamps();
                }
            });
        }
    }

    /**
     * Откатить миграцию
     */
    public function down(): void
    {
        Schema::connection(config('can.connection'))->dropIfExists(config('can.tables.permissionables'));
    }
};
