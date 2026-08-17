<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;

/**
 * Reads the site-wide branding & content settings admins manage from
 * Filament's Platform Settings page (logo, favicon, title, description,
 * about us, contact us) with sensible defaults, and resolves uploaded
 * file paths to public URLs. Shared with every view as `$branding` (see
 * AppServiceProvider) so pages don't need to know about SystemSetting.
 */
class PlatformBranding
{
    public function name(): string
    {
        return SystemSetting::get('site_name') ?: config('app.name', 'Play Snooker');
    }

    public function tagline(): string
    {
        return SystemSetting::get('site_tagline') ?: 'Live betting, digital pool & snooker tournaments.';
    }

    public function description(): string
    {
        return SystemSetting::get('site_description')
            ?: 'Play Snooker bridges physical and digital snooker & pool tournaments with live betting, social multiplayer and an in-game marketplace.';
    }

    public function logoUrl(): ?string
    {
        $path = SystemSetting::get('logo_path');

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function faviconUrl(): ?string
    {
        $path = SystemSetting::get('favicon_path');

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /**
     * Trusted admin-authored HTML (Filament's rich editor) — rendered
     * unescaped on the About Us page.
     */
    public function aboutUs(): string
    {
        return SystemSetting::get('about_us') ?: '<p>Play Snooker is the home of competitive pool and snooker online.</p>';
    }

    public function contactEmail(): ?string
    {
        return SystemSetting::get('contact_email') ?: 'support@playsnooker.bet';
    }

    public function contactPhone(): ?string
    {
        return SystemSetting::get('contact_phone') ?: null;
    }

    public function contactAddress(): ?string
    {
        return SystemSetting::get('contact_address') ?: null;
    }
}
