<?php

namespace App\Services;

use App\Models\Gift;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Handles purchasing a shop product and sending it directly to another
 * user's inventory with an optional personalised message.
 */
class GiftService
{
    public function __construct(
        private readonly WalletService $wallets,
    ) {}

    public function purchaseAndSend(User $sender, User $receiver, Product $product, ?string $message = null): Gift
    {
        if ($sender->id === $receiver->id) {
            throw new InvalidArgumentException('You cannot gift an item to yourself.');
        }

        if (! $product->is_giftable) {
            throw new InvalidArgumentException('This item cannot be gifted.');
        }

        return DB::transaction(function () use ($sender, $receiver, $product, $message) {
            $this->wallets->debit(
                $sender,
                (string) $product->price,
                $product->currency,
                'shop_purchase',
                "Purchased {$product->name} as a gift for {$receiver->name}",
            );

            $inventoryItem = InventoryItem::create([
                'user_id' => $receiver->id,
                'product_id' => $product->id,
                'expires_at' => $product->duration_minutes ? now()->addMinutes($product->duration_minutes) : null,
                'acquired_at' => now(),
            ]);

            return Gift::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'product_id' => $product->id,
                'inventory_item_id' => $inventoryItem->id,
                'message' => $message,
                'status' => 'sent',
            ]);
        });
    }

    public function claim(Gift $gift, User $receiver): Gift
    {
        if ($gift->receiver_id !== $receiver->id) {
            throw new InvalidArgumentException('This gift was not sent to you.');
        }

        $gift->update(['status' => 'claimed', 'claimed_at' => now()]);

        return $gift;
    }
}
