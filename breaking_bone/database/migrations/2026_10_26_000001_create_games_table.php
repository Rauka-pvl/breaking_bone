<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('status', 16)->default('lobby')->index();
            $table->unsignedBigInteger('starts_at_ms')->nullable();
            $table->unsignedBigInteger('ends_at_ms')->nullable();
            $table->unsignedInteger('left_taps')->default(0);
            $table->unsignedInteger('right_taps')->default(0);
            $table->string('winner', 8)->nullable();
            $table->timestamps();
        });

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('client_id', 64);
            $table->string('side', 8);
            $table->string('name', 40)->nullable();
            $table->unsignedInteger('taps')->default(0);
            $table->timestamps();

            $table->unique(['game_id', 'client_id']);
            $table->index(['game_id', 'side']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
        Schema::dropIfExists('games');
    }
};
