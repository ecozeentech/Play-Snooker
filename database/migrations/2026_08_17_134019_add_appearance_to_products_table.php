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
        Schema::table('products', function (Blueprint $table) {
            // Visual customization for the in-game rendering of this item
            // (currently used for cues: shaft/wrap/tip/butt colors so the
            // canvas game engine can render each cue distinctly). Kept
            // generic (rather than cue-specific columns) so future product
            // types (table skins, avatar frames) can reuse the same field.
            $table->json('appearance')->nullable()->after('stats_bonus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('appearance');
        });
    }
};
