<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Services\PlatformBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_branding_falls_back_to_sensible_defaults_when_unconfigured(): void
    {
        $branding = app(PlatformBranding::class);

        $this->assertNotEmpty($branding->name());
        $this->assertNotEmpty($branding->tagline());
        $this->assertNotEmpty($branding->description());
        $this->assertNotEmpty($branding->aboutUs());
        $this->assertNull($branding->logoUrl());
        $this->assertNull($branding->faviconUrl());
    }

    public function test_branding_reflects_admin_configured_system_settings(): void
    {
        SystemSetting::set('site_name', 'Custom Snooker Co', 'string', 'branding');
        SystemSetting::set('about_us', '<p>Custom about us content.</p>', 'string', 'branding');
        SystemSetting::set('contact_email', 'hello@example.com', 'string', 'branding');

        $branding = app(PlatformBranding::class);

        $this->assertSame('Custom Snooker Co', $branding->name());
        $this->assertSame('<p>Custom about us content.</p>', $branding->aboutUs());
        $this->assertSame('hello@example.com', $branding->contactEmail());
    }

    public function test_about_and_contact_pages_render_successfully(): void
    {
        $this->get(route('about'))->assertOk()->assertSee('About');
        $this->get(route('contact'))->assertOk()->assertSee('Contact');
    }
}
