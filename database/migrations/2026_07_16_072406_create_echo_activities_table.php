<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('echo_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('server_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->uuid('event_uuid')
                ->unique();

            $table->string('type', 50)
                ->index();

            $table->uuid('player_uuid')
                ->nullable()
                ->index();

            $table->string('player_name', 16)
                ->nullable();

            $table->string('world', 100);

            $table->double('x');
            $table->double('y');
            $table->double('z');

            $table->string('block_type', 100)
                ->nullable();

            $table->json('metadata')
                ->nullable();

            $table->timestamp('occurred_at');

            $table->timestamps();

            $table->index([
                'server_id',
                'type',
                'occurred_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('echo_activities');
    }
};