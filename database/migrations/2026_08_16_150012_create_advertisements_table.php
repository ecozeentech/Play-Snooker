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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image_url');
            $table->string('redirect_url');
            $table->enum('placement', ['sidebar', 'banner', 'popup'])->index();
            $table->unsignedInteger('impressions_budget')->nullable();
            $table->unsignedInteger('impressions_served')->default(0);
            $table->unsignedInteger('clicks_budget')->nullable();
            $table->unsignedInteger('clicks_served')->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
