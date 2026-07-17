<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'server_commands',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('server_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->uuid('command_uuid')
                    ->unique();

                $table->string(
                    'type',
                    50
                );

                $table->json('payload');

                $table->string(
                    'status',
                    20
                )->default('pending');

                $table->text('message')
                    ->nullable();

                $table->json('result')
                    ->nullable();

                $table->timestamp('claimed_at')
                    ->nullable();

                $table->timestamp('completed_at')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'server_id',
                    'status',
                    'created_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'server_commands'
        );
    }
};
