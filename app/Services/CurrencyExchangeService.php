<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resolves exchange rates against the platform base currency (USD).
 *
 * When OPEN_EXCHANGE_RATES_APP_ID is configured, live fiat rates are fetched
 * from OpenExchangeRates and cached for an hour. Crypto (BTC) rates fall back
 * to the CoinGecko public API. If any external call fails, or no API key is
 * configured, sane static fallback rates are used so the platform keeps
 * working in local/dev/test environments without third-party credentials.
 */
class CurrencyExchangeService
{
    private const CACHE_KEY = 'exchange_rates_v1';

    private const CACHE_TTL_SECONDS = 3600;

    /**
     * Fallback rates expressed as "1 USD = X units of currency".
     */
    private const FALLBACK_RATES = [
        'USD' => 1.0,
        'GBP' => 0.79,
        'EUR' => 0.92,
        'NGN' => 1550.0,
        'BTC' => 0.000016,
        'USDT' => 1.0,
    ];

    public function baseCurrency(): string
    {
        return config('platform.base_currency', 'USD');
    }

    public function supportedCurrencies(): array
    {
        return config('platform.supported_currencies', array_keys(self::FALLBACK_RATES));
    }

    /**
     * @return array<string, float> Map of currency => rate relative to base currency.
     */
    public function rates(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            $rates = self::FALLBACK_RATES;

            $rates = array_merge($rates, $this->fetchFiatRates());
            $rates = array_merge($rates, $this->fetchCryptoRates());

            return $rates;
        });
    }

    public function convert(float|string $amount, string $from, string $to): string
    {
        $amount = (string) $amount;

        if ($from === $to) {
            return $amount;
        }

        $rates = $this->rates();
        $fromRate = (string) ($rates[$from] ?? 1.0);
        $toRate = (string) ($rates[$to] ?? 1.0);

        // Convert to base currency, then to the target currency.
        $inBase = bcdiv($amount, $fromRate, 10);

        return bcmul($inBase, $toRate, 8);
    }

    public function convertToBase(float|string $amount, string $currency): string
    {
        return $this->convert($amount, $currency, $this->baseCurrency());
    }

    private function fetchFiatRates(): array
    {
        $appId = config('services.open_exchange_rates.app_id');

        if (empty($appId)) {
            return [];
        }

        try {
            $response = Http::timeout(5)->get('https://openexchangerates.org/api/latest.json', [
                'app_id' => $appId,
                'symbols' => 'GBP,EUR,NGN',
            ]);

            if ($response->successful()) {
                return $response->json('rates', []);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch OpenExchangeRates rates: '.$e->getMessage());
        }

        return [];
    }

    private function fetchCryptoRates(): array
    {
        try {
            $response = Http::timeout(5)->get(config('services.coingecko.api_url').'/simple/price', [
                'ids' => 'bitcoin,tether',
                'vs_currencies' => 'usd',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $rates = [];

                if (isset($data['bitcoin']['usd']) && $data['bitcoin']['usd'] > 0) {
                    $rates['BTC'] = 1 / $data['bitcoin']['usd'];
                }

                if (isset($data['tether']['usd']) && $data['tether']['usd'] > 0) {
                    $rates['USDT'] = 1 / $data['tether']['usd'];
                }

                return $rates;
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch CoinGecko crypto rates: '.$e->getMessage());
        }

        return [];
    }
}
