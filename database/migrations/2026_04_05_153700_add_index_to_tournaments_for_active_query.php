<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds composite index for start_date and public_hide to optimize tournament queries.
     */
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->index(['start_date', 'public_hide'], 'idx_tournaments_start_date_public_hide');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropIndex('idx_tournaments_start_date_public_hide');
        });
    }
};