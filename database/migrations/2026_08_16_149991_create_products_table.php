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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['cue', 'booster', 'table_skin', 'avatar_frame'])->index();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 6)->default('USD');
            $table->json('stats_bonus')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->boolean('is_giftable')->default(true);
            $table->boolean('is_tradeable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
