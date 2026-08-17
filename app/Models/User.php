<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'wallet_balance',
        'currency_preference',
        'referral_code',
        'referred_by',
        'is_active',
        'is_admin',
        'locale',
        'google_id',
        'facebook_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'wallet_balance' => 'decimal:8',
            'is_active' => 'boolean',
            'is_admin' => 'boolean',
            'deletion_requested_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->referral_code)) {
                $user->referral_code = static::generateUniqueReferralCode();
            }
        });
    }

    public static function generateUniqueReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin && $this->is_active;
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function tournamentRegistrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function createdTournaments(): HasMany
    {
        return $this->hasMany(Tournament::class, 'created_by');
    }

    public function matchesAsPlayerOne(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'player1_id');
    }

    public function matchesAsPlayerTwo(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'player2_id');
    }

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Escrow::class, 'seller_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Escrow::class, 'buyer_id');
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referralAsReferrer(): HasOne
    {
        return $this->hasOne(Referral::class, 'referrer_id');
    }

    public function referralAsReferee(): HasOne
    {
        return $this->hasOne(Referral::class, 'referee_id');
    }

    public function sentGifts(): HasMany
    {
        return $this->hasMany(Gift::class, 'sender_id');
    }

    public function receivedGifts(): HasMany
    {
        return $this->hasMany(Gift::class, 'receiver_id');
    }

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function replays(): HasMany
    {
        return $this->hasMany(MatchReplay::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function wallet(?string $currency = null): ?Wallet
    {
        return $this->wallets()->where('currency', $currency ?? $this->currency_preference)->first();
    }
}
