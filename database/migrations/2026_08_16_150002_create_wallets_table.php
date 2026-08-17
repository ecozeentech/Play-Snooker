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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('currency', ['USD', 'GBP', 'EUR', 'NGN', 'BTC', 'USDT'])->default('USD');
            $table->decimal('balance', 18, 8)->default(0);
            $table->json('ledger')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
