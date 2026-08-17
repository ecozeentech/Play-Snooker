<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Seed default currency, payment gateway and platform fee settings.
     */
    public function run(): void
    {
        $settings = [
            // Supported currencies.
            ['key' => 'supported_currencies', 'value' => ['USD', 'GBP', 'EUR', 'NGN', 'BTC', 'USDT'], 'type' => 'json', 'group' => 'currency'],
            ['key' => 'base_currency', 'value' => 'USD', 'type' => 'string', 'group' => 'currency'],

            // Automated payment gateways (disabled by default until admin adds API keys).
            ['key' => 'auto_gateway_stripe', 'value' => false, 'type' => 'boolean', 'group' => 'gateway'],
            ['key' => 'auto_gateway_coinbase_commerce', 'value' => false, 'type' => 'boolean', 'group' => 'gateway'],
            ['key' => 'auto_gateway_binance_pay', 'value' => false, 'type' => 'boolean', 'group' => 'gateway'],

            // Manual gateways: admin-configured bank transfer / crypto address, verified manually.
            ['key' => 'manual_gateway_bank_transfer', 'value' => true, 'type' => 'boolean', 'group' => 'gateway'],
            ['key' => 'manual_gateway_bank_details', 'value' => "Bank: Play Snooker Ltd\nAccount Name: Play Snooker Ltd\nAccount Number: 0000000000\nSort Code: 00-00-00", 'type' => 'string', 'group' => 'gateway'],
            ['key' => 'manual_gateway_btc', 'value' => true, 'type' => 'boolean', 'group' => 'gateway'],
            ['key' => 'manual_gateway_btc_address', 'value' => 'bc1qexampleaddressplaceholder0000000000', 'type' => 'string', 'group' => 'gateway'],
            ['key' => 'manual_gateway_usdt', 'value' => true, 'type' => 'boolean', 'group' => 'gateway'],
            ['key' => 'manual_gateway_usdt_address', 'value' => 'TExampleUsdtTrc20AddressPlaceholder00', 'type' => 'string', 'group' => 'gateway'],

            // Platform fees.
            ['key' => 'escrow_fee_percent', 'value' => 5, 'type' => 'integer', 'group' => 'fees'],
            ['key' => 'tournament_hosting_fee', 'value' => 10, 'type' => 'integer', 'group' => 'fees'],
            ['key' => 'referral_reward_amount', 'value' => 5, 'type' => 'integer', 'group' => 'fees'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::set($setting['key'], $setting['value'], $setting['type'], $setting['group']);
        }
    }
}
