<?php

namespace App\Models;

use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'avatar',
        'xp_points',
        'level',
        'cue_equipped',
        'total_wins',
        'total_losses',
        'bio',
    ];

    protected function casts(): array
    {
        return [
            'xp_points' => 'integer',
            'level' => 'integer',
            'total_wins' => 'integer',
            'total_losses' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function equippedCue(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'cue_equipped');
    }

    public function winRate(): float
    {
        $total = $this->total_wins + $this->total_losses;

        return $total > 0 ? round(($this->total_wins / $total) * 100, 2) : 0.0;
    }
}
