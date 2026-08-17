<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->nullable()->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('player1_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('player2_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('round')->nullable();
            $table->unsignedInteger('current_frame')->default(1);
            $table->unsignedInteger('frames_to_win')->default(1);
            $table->enum('status', ['scheduled', 'live', 'finished', 'cancelled'])
                ->default('scheduled')
                ->index();
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('frame_scores')->nullable();
            $table->json('odds_data')->nullable();
            $table->boolean('is_streamed')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_matches');
    }
};
