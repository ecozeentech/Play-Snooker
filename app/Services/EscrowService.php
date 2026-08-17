<?php

namespace App\Services;

use App\Models\Escrow;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Escrow flow for peer-to-peer digital asset sales:
 * seller lists item -> buyer pays (funds held in escrow) -> admin verifies
 * delivery -> funds released to seller (minus platform fee), or refunded to
 * the buyer if a dispute is upheld.
 */
class EscrowService
{
    public function __construct(
        private readonly WalletService $wallets,
    ) {}

    public function listItem(User $seller, InventoryItem $item, string $amount, string $currency, string $title, ?string $description = null): Escrow
    {
        if ($item->user_id !== $seller->id) {
            throw new InvalidArgumentException('You can only list items you own.');
        }

        $feePercent = (string) config('platform.escrow_fee_percent');
        $feeAmount = bcdiv(bcmul($amount, $feePercent, 8), '100', 8);

        return Escrow::create([
            'seller_id' => $seller->id,
            'product_id' => $item->product_id,
            'inventory_item_id' => $item->id,
            'title' => $title,
            'description' => $description,
            'amount' => $amount,
            'currency' => $currency,
            'fee_amount' => $feeAmount,
            'status' => 'pending',
        ]);
    }

    /**
     * Buyer pays into escrow; funds are held by the platform until release.
     */
    public function fund(Escrow $escrow, User $buyer): Escrow
    {
        if ($escrow->status !== 'pending' || $escrow->buyer_id !== null) {
            throw new InvalidArgumentException('This listing is no longer available.');
        }

        if ($escrow->seller_id === $buyer->id) {
            throw new InvalidArgumentException('You cannot buy your own listing.');
        }

        return DB::transaction(function () use ($escrow, $buyer) {
            $this->wallets->debit(
                $buyer,
                (string) $escrow->amount,
                $escrow->currency,
                'escrow_hold',
                "Escrow hold for listing #{$escrow->id} ({$escrow->title})",
            );

            $escrow->update(['buyer_id' => $buyer->id]);

            return $escrow;
        });
    }

    /**
     * Release held funds (minus platform fee) to the seller once delivery
     * has been verified by an admin (or automated smart-contract check).
     */
    public function release(Escrow $escrow, ?User $admin = null): Escrow
    {
        if (! in_array($escrow->status, ['pending', 'disputed'], true) || ! $escrow->buyer_id) {
            throw new InvalidArgumentException('This escrow cannot be released yet.');
        }

        return DB::transaction(function () use ($escrow, $admin) {
            $netAmount = $escrow->netSellerAmount();

            $this->wallets->credit(
                $escrow->seller,
                $netAmount,
                $escrow->currency,
                'escrow_release',
                "Escrow release for listing #{$escrow->id} ({$escrow->title})".($admin ? " (reviewed by admin #{$admin->id})" : ''),
            );

            if ($escrow->inventoryItem && $escrow->buyer) {
                $escrow->inventoryItem->update(['user_id' => $escrow->buyer_id, 'is_equipped' => false]);
            }

            $escrow->update(['status' => 'released', 'released_at' => now()]);

            return $escrow;
        });
    }

    /**
     * Refund the buyer in full (e.g. seller failed to deliver, or an admin
     * upholds a dispute in the buyer's favour).
     */
    public function refund(Escrow $escrow, string $reason, ?User $admin = null): Escrow
    {
        if (! $escrow->buyer_id) {
            throw new InvalidArgumentException('This escrow has no funded buyer to refund.');
        }

        return DB::transaction(function () use ($escrow, $reason, $admin) {
            $this->wallets->credit(
                $escrow->buyer,
                (string) $escrow->amount,
                $escrow->currency,
                'escrow_refund',
                "Escrow refund for listing #{$escrow->id}: {$reason}".($admin ? " (admin #{$admin->id})" : ''),
            );

            $escrow->update([
                'status' => 'refunded',
                'resolution_notes' => $reason,
            ]);

            return $escrow;
        });
    }

    public function dispute(Escrow $escrow, string $reason): Escrow
    {
        $escrow->update(['status' => 'disputed', 'dispute_reason' => $reason]);

        return $escrow;
    }
}
