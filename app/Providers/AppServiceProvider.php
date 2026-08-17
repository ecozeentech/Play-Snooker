<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Guards betting endpoints against automated abuse (see
        // routes/web.php and platform.betting.rate_limit_per_minute).
        RateLimiter::for('betting', function ($request) {
            $limit = config('platform.betting.rate_limit_per_minute', 20);

            return Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip());
        });
    }
}
