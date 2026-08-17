<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientFundsException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WalletFundingTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_deposit_request_creates_pending_transaction_without_crediting_wallet(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['wallet_balance' => 0]);

        $response = $this->actingAs($user)->post(route('wallet.deposit.manual'), [
            'amount' => 100,
            'currency' => 'USD',
            'gateway' => 'manual_bank',
            'proof' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $transaction = Transaction::first();
        $this->assertNotNull($transaction);
        $this->assertSame('pending', $transaction->status);
        $this->assertSame('credit', $transaction->type);
        $this->assertEquals(100, (float) $transaction->amount);

        $wallet = Wallet::where('user_id', $user->id)->where('currency', 'USD')->first();
        $this->assertEquals(0, (float) $wallet->balance, 'Pending deposits must not touch the wallet balance yet.');
    }

    public function test_admin_can_approve_pending_deposit_and_wallet_is_credited(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $wallets = app(WalletService::class);

        $transaction = $wallets->requestManualDeposit($user, '50', 'USD', 'manual_bank');

        $wallets->approvePendingDeposit($transaction, $admin);

        $transaction->refresh();
        $this->assertSame('completed', $transaction->status);

        $wallet = Wallet::where('user_id', $user->id)->where('currency', 'USD')->first();
        $this->assertEquals(50, (float) $wallet->balance);

        $user->refresh();
        $this->assertEquals(50, (float) $user->wallet_balance);
    }

    public function test_credit_and_debit_update_wallet_balance_and_ledger(): void
    {
        $user = User::factory()->create();
        $wallets = app(WalletService::class);

        $wallets->credit($user, '200', 'USD', 'test', 'Test credit');
        $wallet = Wallet::where('user_id', $user->id)->where('currency', 'USD')->first();
        $this->assertEquals(200, (float) $wallet->balance);
        $this->assertCount(1, $wallet->ledger);

        $wallets->debit($user, '75', 'USD', 'test', 'Test debit');
        $wallet->refresh();
        $this->assertEquals(125, (float) $wallet->balance);
        $this->assertCount(2, $wallet->ledger);
    }

    public function test_debit_throws_when_wallet_balance_is_insufficient(): void
    {
        $user = User::factory()->create();
        $wallets = app(WalletService::class);

        $this->expectException(InsufficientFundsException::class);

        $wallets->debit($user, '10', 'USD', 'test', 'Should fail');
    }

    public function test_withdrawal_request_holds_funds_until_admin_processes_it(): void
    {
        $user = User::factory()->create();
        $wallets = app(WalletService::class);
        $wallets->credit($user, '100', 'USD', 'test', 'Seed funds');

        $transaction = $wallets->requestWithdrawal($user, '40', 'USD', 'manual_payout');

        $this->assertSame('pending', $transaction->status);

        $wallet = Wallet::where('user_id', $user->id)->where('currency', 'USD')->first();
        $this->assertEquals(60, (float) $wallet->balance, 'Withdrawal funds should be held immediately.');

        $wallets->rejectWithdrawal($transaction, 'Bank details invalid');
        $wallet->refresh();
        $this->assertEquals(100, (float) $wallet->balance, 'Rejected withdrawals must be refunded.');
    }
}
