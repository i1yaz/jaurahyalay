<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained();
            $table->foreignId('tournament_id')->constrained();
            $table->date('date');
            $table->integer('pigeon_number');
            $table->time('start_time')->nullable();
            $table->string('pigeon_time')->nullable();
            $table->integer('pigeon_total')->default(0);
            $table->timestamps();

            $table->unique(['player_id', 'tournament_id', 'date', 'pigeon_number'], 'results_unique_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('results');
    }
};
