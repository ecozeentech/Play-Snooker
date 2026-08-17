<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use Illuminate\Http\RedirectResponse;

class AdClickController extends Controller
{
    /**
     * Tracks a click on an advertisement, then redirects the visitor to
     * the advertiser's target URL. Every rendered <x-ad-banner> links here
     * instead of directly to `redirect_url` so click counts stay accurate
     * for advertisers/admins (see Advertisement::clicks_served).
     */
    public function redirect(Advertisement $ad): RedirectResponse
    {
        $ad->increment('clicks_served');

        return redirect()->away($ad->redirect_url);
    }
}
