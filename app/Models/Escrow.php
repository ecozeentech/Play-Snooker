<?php

namespace App\Models;

use Database\Factories\EscrowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Escrow extends Model
{
    /** @use HasFactory<EscrowFactory> */
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'buyer_id',
        'product_id',
        'inventory_item_id',
        'title',
        'description',
        'amount',
        'currency',
        'fee_amount',
        'status',
        'dispute_reason',
        'resolution_notes',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'fee_amount' => 'decimal:8',
            'released_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function netSellerAmount(): string
    {
        return bcsub((string) $this->amount, (string) $this->fee_amount, 8);
    }
}
