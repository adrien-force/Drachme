<?php

declare(strict_types=1);


namespace Tests\Feature\Accounts;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use App\Support\LogoUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_account_logo_on_create(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => 'Compte logo',
                'type' => AccountType::Checking->value,
                'initial_balance' => '0',
                'logo' => UploadedFile::fake()->image('logo.png', 128, 128),
            ])
            ->assertRedirect();

        $account = Account::query()->first();
        $this->assertNotNull($account);
        $this->assertNotNull($account->logo_path);
        Storage::disk('public')->assertExists($account->logo_path);

        $url = app(LogoUploadService::class)->url($account->logo_path);
        $this->assertNotNull($url);
    }

    public function test_user_can_remove_account_logo_on_update(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'logo_path' => 'logos/accounts/'.$user->id.'/existing.png',
        ]);
        Storage::disk('public')->put($account->logo_path, 'fake');

        $this
            ->actingAs($user)
            ->put(route('accounts.update', $account), [
                'name' => $account->name,
                'type' => AccountType::Checking->value,
                'remove_logo' => true,
            ])
            ->assertRedirect(route('accounts.show', $account));

        $this->assertNull($account->fresh()->logo_path);
        Storage::disk('public')->assertMissing('logos/accounts/'.$user->id.'/existing.png');
    }
}
