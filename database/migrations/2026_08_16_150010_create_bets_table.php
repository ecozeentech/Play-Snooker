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
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('game_matches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 18, 8);
            $table->string('currency', 6)->default('USD');
            $table->decimal('odds', 8, 3);
            $table->enum('type', ['winner', 'frame_winner', 'total_points_over_under'])->index();
            $table->json('selection')->nullable();
            $table->enum('status', ['pending', 'won', 'lost', 'cancelled', 'refunded'])
                ->default('pending')
                ->index();
            $table->decimal('payout', 18, 8)->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
