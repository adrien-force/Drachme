<?php

declare(strict_types=1);

namespace Tests\Feature\ImportProviders;

use App\Enums\ImportColumnField;
use App\Models\ImportProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportProviderShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_displays_provider_configuration(): void
    {
        $user = User::factory()->create();
        $provider = ImportProvider::factory()->for($user)->create([
            'name' => 'Revolut CSV',
            'column_mapping' => [
                'columns' => [
                    ['index' => 0, 'field' => ImportColumnField::Date->value],
                    ['index' => 1, 'field' => ImportColumnField::Label->value],
                    ['index' => 2, 'field' => ImportColumnField::AmountSigned->value],
                    ['index' => 3, 'field' => ImportColumnField::Balance->value],
                ],
            ],
        ]);

        $this
            ->actingAs($user)
            ->get(route('providers.show', $provider))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('providers/providers-show')
                ->where('provider.name', 'Revolut CSV')
                ->has('fieldOptions', 7)
                ->where('provider.column_mapping.columns.3.field', ImportColumnField::Balance->value));
    }

    public function test_user_cannot_view_another_users_provider(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $provider = ImportProvider::factory()->for($owner)->create();

        $this
            ->actingAs($intruder)
            ->get(route('providers.show', $provider))
            ->assertForbidden();
    }
}
