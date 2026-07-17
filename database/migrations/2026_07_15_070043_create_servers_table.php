<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('host');

            $table->unsignedSmallInteger('port')
                ->default(25565);

            $table->string('status')
                ->default('offline');

            $table->string('minecraft_version')
                ->nullable();

            $table->unsignedInteger('players_online')
                ->default(0);

            $table->unsignedInteger('players_max')
                ->default(0);

            $table->timestamp('last_seen_at')
                ->nullable();

            $table->string('api_token', 64)
                ->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};