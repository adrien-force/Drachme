<?php

declare(strict_types=1);


namespace Tests\Feature\Shell;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShellNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function placeholderRoutesProvider(): array
    {
        return [
            'accounts' => ['accounts.index'],
            'transactions' => ['transactions.index'],
            'categories' => ['categories.index'],
            'category-rules' => ['category-rules.index'],
            'providers' => ['providers.index'],
            'import' => ['import.index'],
            'investments' => ['investments.index'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('placeholderRoutesProvider')]
    public function test_authenticated_user_can_open_shell_placeholder_pages(string $routeName): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route($routeName))
            ->assertOk();
    }

    public function test_unknown_shell_page_returns_404(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/accounts/extra-segment')
            ->assertNotFound();
    }
}
