<?php

namespace App\Providers;

use App\Services\PlatformBranding;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PlatformBranding::class);
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

        // Admin-editable branding (logo, title, description, about/contact
        // info — see App\Filament\Pages\PlatformSettings) is available in
        // every Blade view as `$branding` without each page needing to
        // fetch it manually.
        View::share('branding', $this->app->make(PlatformBranding::class));
    }
}
