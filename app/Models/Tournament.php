<?php

namespace App\Models;

use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    /** @use HasFactory<TournamentFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'format',
        'status',
        'entry_fee',
        'prize_pool',
        'currency',
        'max_players',
        'registration_closes_at',
        'started_at',
        'finished_at',
        'created_by',
        'is_user_created',
        'hosting_fee_paid',
        'check_in_enabled',
        'check_in_opens_at',
        'bracket_data',
    ];

    protected function casts(): array
    {
        return [
            'entry_fee' => 'decimal:2',
            'prize_pool' => 'decimal:2',
            'hosting_fee_paid' => 'decimal:2',
            'is_user_created' => 'boolean',
            'check_in_enabled' => 'boolean',
            'registration_closes_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'check_in_opens_at' => 'datetime',
            'bracket_data' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function isPhysical(): bool
    {
        return $this->type === 'physical';
    }

    public function isFull(): bool
    {
        return $this->registrations()->count() >= $this->max_players;
    }
}
