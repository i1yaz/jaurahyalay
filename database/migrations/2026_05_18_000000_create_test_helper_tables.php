<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!app()->runningUnitTests() && !app()->environment('testing')) {
            return;
        }

        if (!Schema::hasTable('news')) {
            Schema::create('news', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->boolean('show')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sliders')) {
            Schema::create('sliders', function (Blueprint $table) {
                $table->id();
                $table->boolean('show')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sponsors')) {
            Schema::create('sponsors', function (Blueprint $table) {
                $table->id();
                $table->boolean('show')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tournament_prizes')) {
            Schema::create('tournament_prizes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tournament_id');
                $table->string('name');
                $table->string('position');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!app()->runningUnitTests() && !app()->environment('testing')) {
            return;
        }

        Schema::dropIfExists('tournament_prizes');
        Schema::dropIfExists('sponsors');
        Schema::dropIfExists('sliders');
        Schema::dropIfExists('news');
    }
};
