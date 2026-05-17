<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Support\LogoUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_profile_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'locale' => 'fr',
                'month_start_day' => 1,
                'avatar' => UploadedFile::fake()->image('avatar.png', 128, 128),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);

        $url = app(LogoUploadService::class)->url($user->avatar_path);
        $this->assertNotNull($url);
    }

    public function test_month_start_day_is_stored_on_user_only(): void
    {
        $user = User::factory()->create(['month_start_day' => 1]);

        $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'locale' => 'fr',
                'month_start_day' => 27,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(27, $user->fresh()->month_start_day);
    }
}
