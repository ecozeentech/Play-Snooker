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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit'])->index();
            $table->decimal('amount', 18, 8);
            $table->string('currency', 6)->default('USD');
            $table->decimal('amount_usd', 18, 8)->nullable();
            $table->string('gateway')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending')->index();
            $table->string('reference')->unique();
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
