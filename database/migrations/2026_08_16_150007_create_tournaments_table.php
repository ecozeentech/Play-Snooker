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
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['physical', 'digital'])->index();
            $table->enum('format', ['single_elimination', 'double_elimination', 'round_robin'])
                ->default('single_elimination');
            $table->enum('status', ['upcoming', 'ongoing', 'finished', 'cancelled'])
                ->default('upcoming')
                ->index();
            $table->decimal('entry_fee', 12, 2)->default(0);
            $table->decimal('prize_pool', 12, 2)->default(0);
            $table->string('currency', 6)->default('USD');
            $table->unsignedInteger('max_players');
            $table->timestamp('registration_closes_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_user_created')->default(false);
            $table->decimal('hosting_fee_paid', 12, 2)->default(0);
            $table->boolean('check_in_enabled')->default(false);
            $table->timestamp('check_in_opens_at')->nullable();
            $table->json('bracket_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
