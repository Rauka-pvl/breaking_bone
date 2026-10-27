<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropIndex(['game_id', 'side']);
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('side');
            $table->unsignedBigInteger('reached_ms')->nullable()->after('taps');
        });

        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['left_taps', 'right_taps', 'winner']);
            $table->unsignedBigInteger('winner_player_id')->nullable()->after('ends_at_ms');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('winner_player_id');
            $table->unsignedInteger('left_taps')->default(0);
            $table->unsignedInteger('right_taps')->default(0);
            $table->string('winner', 8)->nullable();
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('reached_ms');
            $table->string('side', 8)->default('left');
            $table->index(['game_id', 'side']);
        });
    }
};
