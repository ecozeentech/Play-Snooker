<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Handles purchasing shop products (cues, boosters, skins, avatar frames)
 * directly into the buyer's own inventory using their wallet balance.
 */
class ShopService
{
    public function __construct(
        private readonly WalletService $wallets,
    ) {}

    public function purchase(User $buyer, Product $product): InventoryItem
    {
        if (! $product->is_active) {
            throw new InvalidArgumentException('This item is no longer available.');
        }

        return DB::transaction(function () use ($buyer, $product) {
            $this->wallets->debit(
                $buyer,
                (string) $product->price,
                $product->currency,
                'shop_purchase',
                "Purchased {$product->name}",
            );

            return InventoryItem::create([
                'user_id' => $buyer->id,
                'product_id' => $product->id,
                'expires_at' => $product->duration_minutes ? now()->addMinutes($product->duration_minutes) : null,
                'acquired_at' => now(),
            ]);
        });
    }

    public function equip(User $user, InventoryItem $item): InventoryItem
    {
        if ($item->user_id !== $user->id) {
            throw new InvalidArgumentException('You do not own this item.');
        }

        DB::transaction(function () use ($user, $item) {
            InventoryItem::query()
                ->where('user_id', $user->id)
                ->whereHas('product', fn ($q) => $q->where('type', $item->product->type))
                ->update(['is_equipped' => false]);

            $item->update(['is_equipped' => true]);

            if ($item->product->type === 'cue') {
                $user->profile?->update(['cue_equipped' => $item->product_id]);
            }
        });

        return $item->refresh();
    }
}
