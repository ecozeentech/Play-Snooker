<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base Currency
    |--------------------------------------------------------------------------
    |
    | All wallet balances are stored internally in this currency. Display
    | and withdrawal amounts are converted on the fly using the currency
    | exchange service.
    |
    */
    'base_currency' => env('PLATFORM_BASE_CURRENCY', 'USD'),

    'supported_currencies' => array_filter(explode(',', env(
        'PLATFORM_SUPPORTED_CURRENCIES',
        'USD,GBP,EUR,NGN,BTC,USDT'
    ))),

    /*
    |--------------------------------------------------------------------------
    | Platform Fees
    |--------------------------------------------------------------------------
    */
    'escrow_fee_percent' => (float) env('PLATFORM_ESCROW_FEE_PERCENT', 5),
    'tournament_hosting_fee' => (float) env('PLATFORM_TOURNAMENT_HOSTING_FEE', 10),
    'referral_reward_amount' => (float) env('PLATFORM_REFERRAL_REWARD_AMOUNT', 5),

    /*
    |--------------------------------------------------------------------------
    | Betting
    |--------------------------------------------------------------------------
    */
    'betting' => [
        'min_stake' => 0.5,
        'max_stake' => 5000,
        'rate_limit_per_minute' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Game Engine
    |--------------------------------------------------------------------------
    */
    'game' => [
        'ai_difficulties' => ['easy', 'medium', 'hard'],
        'max_replays_per_user' => 5,
    ],

];
