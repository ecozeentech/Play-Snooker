<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'image_url',
        'price',
        'currency',
        'stats_bonus',
        'appearance',
        'duration_minutes',
        'is_giftable',
        'is_tradeable',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stats_bonus' => 'array',
            'appearance' => 'array',
            'is_giftable' => 'boolean',
            'is_tradeable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Default cue appearance used when a cue product doesn't define its
     * own custom colors (e.g. legacy seeded cues created before this
     * feature existed).
     */
    public const DEFAULT_CUE_APPEARANCE = [
        'shaft_color' => '#c1935c',
        'wrap_color' => '#4a301d',
        'tip_color' => '#2b6cb0',
        'butt_color' => '#1f140c',
    ];

    public function cueAppearance(): array
    {
        return array_merge(self::DEFAULT_CUE_APPEARANCE, $this->appearance ?? []);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function gifts(): HasMany
    {
        return $this->hasMany(Gift::class);
    }

    public function isTemporaryBoost(): bool
    {
        return $this->type === 'booster' && ! empty($this->duration_minutes);
    }
}
