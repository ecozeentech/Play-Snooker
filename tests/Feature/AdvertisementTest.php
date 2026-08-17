<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvertisementTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_ad_banner_renders_on_the_dashboard_and_counts_an_impression(): void
    {
        $ad = Advertisement::factory()->create([
            'placement' => 'sidebar',
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'impressions_served' => 0,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee($ad->title);

        $this->assertEquals(1, $ad->fresh()->impressions_served);
    }

    public function test_expired_ad_is_not_shown(): void
    {
        Advertisement::factory()->create([
            'title' => 'Expired Campaign',
            'placement' => 'sidebar',
            'is_active' => true,
            'start_date' => now()->subMonth(),
            'end_date' => now()->subDay(),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Expired Campaign');
    }

    public function test_clicking_an_ad_increments_clicks_and_redirects_to_the_advertiser(): void
    {
        $ad = Advertisement::factory()->create([
            'redirect_url' => 'https://advertiser.example.com',
            'clicks_served' => 0,
        ]);

        $response = $this->get(route('ads.click', $ad));

        $response->assertRedirect('https://advertiser.example.com');
        $this->assertEquals(1, $ad->fresh()->clicks_served);
    }

    public function test_ad_stops_serving_once_budget_is_exhausted(): void
    {
        $ad = Advertisement::factory()->create([
            'placement' => 'sidebar',
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'impressions_budget' => 5,
            'impressions_served' => 5,
        ]);

        $this->assertFalse($ad->hasBudgetRemaining());

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee($ad->title);
    }
}
