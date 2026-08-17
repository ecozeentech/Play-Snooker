<?php

namespace App\Services;

use App\Exceptions\InsufficientFundsException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Central service for all wallet mutations (deposits, withdrawals, bet
 * stakes/payouts, purchases, gifts and internal transfers).
 *
 * Every balance mutation is wrapped in a database transaction with a
 * row lock on the wallet to keep concurrent operations (e.g. simultaneous
 * bets) consistent, and is always paired with a Transaction ledger record
 * for auditability.
 */
class WalletService
{
    public function __construct(
        private readonly CurrencyExchangeService $exchange,
    ) {}

    public function walletFor(User $user, ?string $currency = null): Wallet
    {
        $currency = $currency ?? $user->currency_preference ?? config('platform.base_currency');

        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id, 'currency' => $currency],
            ['balance' => 0, 'ledger' => []],
        );
    }

    /**
     * Credit a user's wallet (deposit, bet payout, referral reward, gift, sale proceeds, ...).
     */
    public function credit(
        User $user,
        string $amount,
        string $currency,
        string $gateway,
        string $description,
        string $status = 'completed',
        array $meta = [],
    ): Transaction {
        return DB::transaction(function () use ($user, $amount, $currency, $gateway, $description, $status, $meta) {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->first() ?? $this->walletFor($user, $currency);

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $amount,
                'currency' => $currency,
                'amount_usd' => $this->exchange->convertToBase($amount, $currency),
                'gateway' => $gateway,
                'status' => $status,
                'reference' => $meta['reference'] ?? (string) Str::uuid(),
                'description' => $description,
                'meta' => $meta,
            ]);

            if ($status === 'completed') {
                $wallet->balance = bcadd((string) $wallet->balance, $amount, 8);
                $wallet->appendLedgerEntry([
                    'type' => 'credit',
                    'amount' => $amount,
                    'description' => $description,
                    'transaction_id' => $transaction->id,
                ]);
                $wallet->save();

                $this->recalculateUserBalance($user);
            }

            return $transaction;
        });
    }

    /**
     * Debit a user's wallet (withdrawal, bet stake, purchase, escrow hold, ...).
     *
     * @throws InsufficientFundsException
     */
    public function debit(
        User $user,
        string $amount,
        string $currency,
        string $gateway,
        string $description,
        array $meta = [],
    ): Transaction {
        return DB::transaction(function () use ($user, $amount, $currency, $gateway, $description, $meta) {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->first() ?? $this->walletFor($user, $currency);

            if (bccomp((string) $wallet->balance, $amount, 8) < 0) {
                throw InsufficientFundsException::forAmount($amount, $currency);
            }

            $wallet->balance = bcsub((string) $wallet->balance, $amount, 8);
            $wallet->appendLedgerEntry([
                'type' => 'debit',
                'amount' => $amount,
                'description' => $description,
            ]);
            $wallet->save();

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $amount,
                'currency' => $currency,
                'amount_usd' => $this->exchange->convertToBase($amount, $currency),
                'gateway' => $gateway,
                'status' => 'completed',
                'reference' => $meta['reference'] ?? (string) Str::uuid(),
                'description' => $description,
                'meta' => $meta,
            ]);

            $this->recalculateUserBalance($user);

            return $transaction;
        });
    }

    /**
     * Request a withdrawal: funds are debited from the wallet immediately
     * (so they can't be spent twice) and the transaction is left pending
     * until an admin processes the payout via the chosen gateway.
     */
    public function requestWithdrawal(User $user, string $amount, string $currency, string $gateway, array $meta = []): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $currency, $gateway, $meta) {
            $transaction = $this->debit($user, $amount, $currency, $gateway, 'Withdrawal request pending payout', $meta);
            $transaction->update(['status' => 'pending']);

            return $transaction;
        });
    }

    /**
     * Mark a pending withdrawal as paid out by the admin.
     */
    public function completeWithdrawal(Transaction $transaction): Transaction
    {
        $transaction->update(['status' => 'completed']);

        return $transaction;
    }

    /**
     * Reject a pending withdrawal and refund the held funds back to the wallet.
     */
    public function rejectWithdrawal(Transaction $transaction, string $reason): Transaction
    {
        return DB::transaction(function () use ($transaction, $reason) {
            $this->credit(
                $transaction->user,
                (string) $transaction->amount,
                $transaction->currency,
                'withdrawal_refund',
                "Withdrawal request refunded: {$reason}",
            );

            $transaction->update([
                'status' => 'cancelled',
                'meta' => array_merge($transaction->meta ?? [], ['rejection_reason' => $reason]),
            ]);

            return $transaction;
        });
    }

    /**
     * Move funds between two users' wallets atomically (gifts, escrow, P2P transfers).
     *
     * @return array{0: Transaction, 1: Transaction} [debitTransaction, creditTransaction]
     */
    public function transfer(User $from, User $to, string $amount, string $currency, string $description): array
    {
        return DB::transaction(function () use ($from, $to, $amount, $currency, $description) {
            $debit = $this->debit($from, $amount, $currency, 'internal_transfer', $description);
            $credit = $this->credit($to, $amount, $currency, 'internal_transfer', $description);

            return [$debit, $credit];
        });
    }

    /**
     * Recalculate and persist the denormalised `users.wallet_balance` cache,
     * expressed in the platform base currency, from the sum of all wallets.
     */
    public function recalculateUserBalance(User $user): void
    {
        $total = $user->wallets()->get()->reduce(
            fn (string $carry, Wallet $wallet) => bcadd($carry, $this->exchange->convertToBase((string) $wallet->balance, $wallet->currency), 8),
            '0',
        );

        $user->forceFill(['wallet_balance' => $total])->save();
    }

    /**
     * Initiate a manual deposit request (bank transfer / manual crypto
     * address). The transaction is recorded as pending and does not touch
     * the wallet balance until an admin verifies the payment proof and
     * approves it via {@see approvePendingDeposit()}.
     */
    public function requestManualDeposit(User $user, string $amount, string $currency, string $gateway, ?string $proofPath = null): Transaction
    {
        return $this->credit(
            $user,
            $amount,
            $currency,
            $gateway,
            'Manual deposit awaiting admin verification',
            status: 'pending',
            meta: ['proof_path' => $proofPath],
        );
    }

    /**
     * Admin approves a pending manual deposit: credits the wallet and marks
     * the transaction completed. Safe to call only once per transaction.
     */
    public function approvePendingDeposit(Transaction $transaction, ?User $admin = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $admin) {
            if ($transaction->status !== 'pending' || $transaction->type !== 'credit') {
                return $transaction;
            }

            $wallet = $this->walletFor($transaction->user, $transaction->currency);
            $wallet->balance = bcadd((string) $wallet->balance, (string) $transaction->amount, 8);
            $wallet->appendLedgerEntry([
                'type' => 'credit',
                'amount' => (string) $transaction->amount,
                'description' => 'Manual deposit approved'.($admin ? " by admin #{$admin->id}" : ''),
                'transaction_id' => $transaction->id,
            ]);
            $wallet->save();

            $transaction->update(['status' => 'completed']);

            $this->recalculateUserBalance($transaction->user);

            return $transaction;
        });
    }

    public function rejectPendingDeposit(Transaction $transaction, string $reason): Transaction
    {
        $transaction->update([
            'status' => 'failed',
            'meta' => array_merge($transaction->meta ?? [], ['rejection_reason' => $reason]),
        ]);

        return $transaction;
    }

    /**
     * Admin manual override: adjust a wallet balance directly, always
     * generating an auditable transaction record.
     */
    public function adjustBalance(User $user, string $amount, string $currency, string $reason, ?User $admin = null): Transaction
    {
        $type = bccomp($amount, '0', 8) >= 0 ? 'credit' : 'debit';
        $absoluteAmount = ltrim($amount, '-');

        return $type === 'credit'
            ? $this->credit($user, $absoluteAmount, $currency, 'admin_override', $reason, meta: ['admin_id' => $admin?->id])
            : $this->debit($user, $absoluteAmount, $currency, 'admin_override', $reason, meta: ['admin_id' => $admin?->id]);
    }
}
