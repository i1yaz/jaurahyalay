<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('player_tournament_total', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained();
            $table->date('date');
            $table->foreignId('player_id')->constrained();
            $table->integer('landed')->default(0);
            $table->integer('total')->default(0);
            $table->timestamps();

            $table->unique(['tournament_id', 'date', 'player_id'], 'player_tournament_total_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('player_tournament_total');
    }
};
