<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CueAppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cue_appearance_merges_custom_colors_over_defaults(): void
    {
        $cue = Product::factory()->create([
            'type' => 'cue',
            'appearance' => ['shaft_color' => '#123456'],
        ]);

        $appearance = $cue->cueAppearance();

        $this->assertSame('#123456', $appearance['shaft_color']);
        $this->assertSame(Product::DEFAULT_CUE_APPEARANCE['tip_color'], $appearance['tip_color']);
    }

    public function test_cue_appearance_falls_back_entirely_to_defaults_when_unset(): void
    {
        $cue = Product::factory()->create(['type' => 'cue', 'appearance' => null]);

        $this->assertSame(Product::DEFAULT_CUE_APPEARANCE, $cue->cueAppearance());
    }

    public function test_practice_page_lists_the_house_cue_and_every_owned_cue(): void
    {
        $user = User::factory()->create();
        $ownedCue = Product::factory()->create(['type' => 'cue', 'name' => 'Golden Break Cue']);

        InventoryItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $ownedCue->id,
            'is_equipped' => true,
        ]);

        $response = $this->actingAs($user)->get(route('game.practice'));

        $response->assertOk();
        $response->assertSee('House Cue');
        $response->assertSee('Golden Break Cue');
    }
}
