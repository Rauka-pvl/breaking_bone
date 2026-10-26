<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('key', 40)->unique();
            $table->string('code', 16)->unique();
            $table->boolean('active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::table('games', function (Blueprint $table) {
            $table->foreignId('host_key_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropConstrainedForeignId('host_key_id');
        });

        Schema::dropIfExists('host_keys');
    }
};
