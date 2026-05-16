<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Support\ThemeColors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppearanceUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_appearance_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('appearance.edit'))
            ->assertOk();
    }

    public function test_user_can_save_custom_theme_colors(): void
    {
        $user = User::factory()->create();

        $custom = [
            'primary' => '#112233',
            'chart_income' => '#112233',
            'chart_expense' => '#aabbcc',
            'chart_net_worth' => '#445566',
            'chart_secondary' => '#778899',
        ];

        $this
            ->actingAs($user)
            ->patch(route('appearance.update'), ['colors' => $custom])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('appearance.edit'));

        $this->assertSame($custom, $user->fresh()->theme_colors);
    }

    public function test_user_can_reset_theme_colors(): void
    {
        $user = User::factory()->create([
            'theme_colors' => ['primary' => '#112233'],
        ]);

        $this
            ->actingAs($user)
            ->post(route('appearance.reset'))
            ->assertRedirect(route('appearance.edit'));

        $this->assertNull($user->fresh()->theme_colors);
    }

    public function test_resolved_theme_falls_back_to_defaults_when_null(): void
    {
        $user = User::factory()->create(['theme_colors' => null]);

        $this->assertSame(ThemeColors::defaults(), ThemeColors::resolve($user));
    }
}
