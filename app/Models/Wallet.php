<?php

namespace App\Models;

use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency',
        'balance',
        'ledger',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:8',
            'ledger' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appendLedgerEntry(array $entry): void
    {
        $ledger = $this->ledger ?? [];
        $ledger[] = array_merge($entry, ['at' => now()->toIso8601String()]);

        $this->ledger = $ledger;
    }
}
