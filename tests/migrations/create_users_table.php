<?php

namespace dmitryrogolev\Can\Tests\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = config('can.connection');
        $table = app(config('can.models.user'))->getTable();

        if (! Schema::connection($connection)->hasTable($table)) {
            Schema::connection($connection)->create($table, function (Blueprint $table) {
                if (config('can.uses.uuid')) {
                    $table->uuid(config('can.primary_key'));
                } else {
                    $table->id();
                }

                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();

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
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('can.connection'))->dropIfExists(app(config('can.models.user'))->getTable());
    }
};
