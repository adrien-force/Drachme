<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedDemoDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_demo_creates_dedicated_user_without_touching_existing_data(): void
    {
        $existing = User::factory()->create(['email' => 'me@example.com']);
        Account::factory()->for($existing)->create(['name' => 'My real account']);
        Transaction::factory()->for($existing)->count(2)->create();

        $usersBefore = User::query()->count();

        $this->artisan('drachme:seed-demo')
            ->assertSuccessful();

        $this->assertSame($usersBefore + 1, User::query()->count());

        $demo = User::query()->where('email', DemoDataSeeder::DEMO_EMAIL)->first();
        $this->assertNotNull($demo);
        $this->assertSame('Alex Morgan', $demo->name);

        $this->assertSame(1, Account::query()->where('user_id', $existing->id)->count());
        $this->assertSame(2, Transaction::query()->where('user_id', $existing->id)->count());
        $this->assertGreaterThan(0, Account::query()->where('user_id', $demo->id)->count());
        $this->assertGreaterThan(0, Transaction::query()->where('user_id', $demo->id)->count());
    }

    public function test_seed_demo_is_idempotent_without_fresh_flag(): void
    {
        $this->artisan('drachme:seed-demo')->assertSuccessful();
        $accountsAfterFirst = Account::query()->whereHas('user', fn ($q) => $q->where('email', DemoDataSeeder::DEMO_EMAIL))->count();

        $this->artisan('drachme:seed-demo')->assertSuccessful();
        $accountsAfterSecond = Account::query()->whereHas('user', fn ($q) => $q->where('email', DemoDataSeeder::DEMO_EMAIL))->count();

        $this->assertSame($accountsAfterFirst, $accountsAfterSecond);
    }

    public function test_seed_demo_fresh_rebuilds_demo_user_only(): void
    {
        User::factory()->create(['email' => 'me@example.com']);

        $this->artisan('drachme:seed-demo')->assertSuccessful();
        $this->artisan('drachme:seed-demo --fresh')->assertSuccessful();

        $demo = User::query()->where('email', DemoDataSeeder::DEMO_EMAIL)->first();
        $this->assertNotNull($demo);
        $this->assertSame(3, Account::query()->where('user_id', $demo->id)->count());
        $this->assertSame(1, User::query()->where('email', 'me@example.com')->count());
    }
}
