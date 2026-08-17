<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\CurrencyExchangeService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly CurrencyExchangeService $exchange,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user()->load('wallets');
        $transactions = $user->transactions()->latest()->paginate(20);

        return view('wallet.index', [
            'wallets' => $user->wallets,
            'transactions' => $transactions,
            'supportedCurrencies' => $this->exchange->supportedCurrencies(),
            'gateways' => [
                'stripe' => SystemSetting::get('auto_gateway_stripe', false),
                'coinbase_commerce' => SystemSetting::get('auto_gateway_coinbase_commerce', false),
                'binance_pay' => SystemSetting::get('auto_gateway_binance_pay', false),
                'manual_bank' => SystemSetting::get('manual_gateway_bank_transfer', true),
                'manual_btc' => SystemSetting::get('manual_gateway_btc', true),
                'manual_usdt' => SystemSetting::get('manual_gateway_usdt', true),
            ],
            'bankDetails' => SystemSetting::get('manual_gateway_bank_details'),
            'btcAddress' => SystemSetting::get('manual_gateway_btc_address'),
            'usdtAddress' => SystemSetting::get('manual_gateway_usdt_address'),
        ]);
    }

    public function depositManual(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'in:USD,GBP,EUR,NGN,BTC,USDT'],
            'gateway' => ['required', 'in:manual_bank,manual_btc,manual_usdt'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $path = $request->file('proof')->store('deposit-proofs', 'local');

        $this->wallets->requestManualDeposit(
            $request->user(),
            (string) $data['amount'],
            $data['currency'],
            $data['gateway'],
            $path,
        );

        return back()->with('success', 'Deposit submitted! An admin will verify your payment proof shortly.');
    }

    public function withdraw(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'in:USD,GBP,EUR,NGN,BTC,USDT'],
            'destination' => ['required', 'string', 'max:255'],
        ]);

        $this->wallets->requestWithdrawal(
            $request->user(),
            (string) $data['amount'],
            $data['currency'],
            'manual_payout',
            ['destination' => $data['destination']],
        );

        return back()->with('success', 'Withdrawal requested. Funds have been held pending payout processing.');
    }

    public function switchCurrency(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'currency' => ['required', 'string', 'in:USD,GBP,EUR,NGN,BTC,USDT'],
        ]);

        $request->user()->update(['currency_preference' => $data['currency']]);
        $this->wallets->walletFor($request->user(), $data['currency']);

        return back()->with('success', "Display currency switched to {$data['currency']}.");
    }
}
