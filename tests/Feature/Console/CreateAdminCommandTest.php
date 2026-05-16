<?php

declare(strict_types=1);


namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_verified_admin_user(): void
    {
        $this->artisan('drachme:create-admin', [
            '--name' => 'Admin Drachme',
            '--email' => 'admin@drachme.test',
            '--password' => 'password',
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@drachme.test',
            'name' => 'Admin Drachme',
        ]);

        $user = User::query()->where('email', 'admin@drachme.test')->first();
        $this->assertNotNull($user?->email_verified_at);
    }

    public function test_fails_when_email_already_exists(): void
    {
        User::factory()->create(['email' => 'admin@drachme.test']);

        $this->artisan('drachme:create-admin', [
            '--email' => 'admin@drachme.test',
            '--password' => 'password',
        ])
            ->assertFailed();
    }
}
