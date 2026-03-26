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
        Schema::table('results', function (Blueprint $table) {
            $table->boolean('is_double_stamp')->default(false)->after('pigeon_total');
        });

        Schema::table('player_tournament_total', function (Blueprint $table) {
            $table->integer('double_stamp_landed')->default(0)->after('total');
            $table->integer('double_stamp_total')->default(0)->after('double_stamp_landed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropColumn('is_double_stamp');
        });

        Schema::table('player_tournament_total', function (Blueprint $table) {
            $table->dropColumn(['double_stamp_landed', 'double_stamp_total']);
        });
    }
};
