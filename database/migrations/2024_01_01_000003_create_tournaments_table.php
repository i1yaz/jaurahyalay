<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('club_id')->constrained();
            $table->integer('days')->default(1);
            $table->boolean('status')->default(true);
            $table->boolean('show')->default(true);
            $table->integer('pigeons')->default(7);
            $table->date('start_date')->nullable();
            $table->time('start_time')->default('06:00:00');
            $table->integer('supporter')->default(0);
            $table->string('poster')->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tournaments');
    }
};
