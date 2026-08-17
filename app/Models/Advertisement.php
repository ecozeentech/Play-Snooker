<?php

namespace App\Models;

use Database\Factories\AdvertisementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Advertisement extends Model
{
    /** @use HasFactory<AdvertisementFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'image_url',
        'redirect_url',
        'placement',
        'impressions_budget',
        'impressions_served',
        'clicks_budget',
        'clicks_served',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now());
    }

    public function scopePlacement($query, string $placement)
    {
        return $query->where('placement', $placement);
    }

    public function hasBudgetRemaining(): bool
    {
        $impressionsOk = $this->impressions_budget === null || $this->impressions_served < $this->impressions_budget;
        $clicksOk = $this->clicks_budget === null || $this->clicks_served < $this->clicks_budget;

        return $impressionsOk && $clicksOk;
    }

    /**
     * Resolves `image_url` to a renderable URL. Admin-uploaded banners
     * (via Filament's FileUpload) store a relative path on the `public`
     * disk; seeded/legacy records may already hold a full external URL.
     * Deliberately a plain method (not an accessor) so it doesn't shadow
     * the raw attribute value Filament's FileUpload needs when hydrating
     * the edit form.
     */
    public function displayImageUrl(): ?string
    {
        if (! $this->image_url) {
            return null;
        }

        return Str::startsWith($this->image_url, ['http://', 'https://'])
            ? $this->image_url
            : Storage::disk('public')->url($this->image_url);
    }
}
