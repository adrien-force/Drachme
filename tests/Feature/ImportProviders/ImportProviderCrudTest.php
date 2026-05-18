<?php

declare(strict_types=1);


namespace Tests\Feature\ImportProviders;

use App\Enums\AccountType;
use App\Enums\ImportColumnField;
use App\Enums\ImportProviderType;
use App\Models\Account;
use App\Models\ImportProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportProviderCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(?int $defaultAccountId = null): array
    {
        return [
            'name' => 'BNP relevé courant',
            'default_account_id' => $defaultAccountId,
            'import_type' => ImportProviderType::Transactions->value,
            'column_mapping' => [
                'columns' => [
                    ['index' => 0, 'field' => ImportColumnField::Date->value],
                    ['index' => 1, 'field' => ImportColumnField::Label->value],
                    ['index' => 2, 'field' => ImportColumnField::Debit->value],
                    ['index' => 3, 'field' => ImportColumnField::Credit->value],
                ],
            ],
            'csv_options' => [
                'delimiter' => ';',
                'date_format' => 'd/m/Y',
            ],
        ];
    }

    public function test_user_can_create_import_provider(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $this
            ->actingAs($user)
            ->post(route('providers.store'), $this->validPayload($account->id))
            ->assertRedirect(route('providers.index'));

        $provider = ImportProvider::query()->first();

        $this->assertNotNull($provider);
        $this->assertSame($user->id, $provider->user_id);
        $this->assertSame($account->id, $provider->default_account_id);
    }

    public function test_user_can_link_multiple_accounts_to_provider(): void
    {
        $user = User::factory()->create();
        $checking = Account::factory()->for($user)->create(['name' => 'Checking']);
        $invest = Account::factory()->for($user)->create([
            'name' => 'PEA',
            'type' => AccountType::Invest,
        ]);

        $payload = $this->validPayload($checking->id);
        $payload['account_ids'] = [$checking->id, $invest->id];

        $this
            ->actingAs($user)
            ->post(route('providers.store'), $payload)
            ->assertRedirect(route('providers.index'));

        $provider = ImportProvider::query()->first();
        $this->assertNotNull($provider);
        $this->assertEqualsCanonicalizing(
            [$checking->id, $invest->id],
            $provider->accounts()->pluck('accounts.id')->all(),
        );
    }

    public function test_default_account_must_belong_to_same_user(): void
    {
        $user = User::factory()->create();
        $otherAccount = Account::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('providers.store'), $this->validPayload($otherAccount->id))
            ->assertSessionHasErrors('default_account_id');
    }

    public function test_mapping_requires_date_label_and_amount_strategy(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('providers.store'), [
                'name' => 'Invalid mapping',
                'import_type' => ImportProviderType::Transactions->value,
                'column_mapping' => [
                    'columns' => [
                        ['index' => 0, 'field' => ImportColumnField::Skip->value],
                    ],
                ],
            ])
            ->assertSessionHasErrors('column_mapping');
    }

    public function test_edit_form_includes_saved_mapping(): void
    {
        $user = User::factory()->create();
        $provider = ImportProvider::factory()->for($user)->create();

        $this
            ->actingAs($user)
            ->get(route('providers.edit', $provider))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('providers/providers-form')
                ->has('provider.column_mapping.columns', 3)
                ->where('provider.id', $provider->id)
                ->where('provider.csv_options.delimiter', ';'));
    }

    public function test_user_can_update_and_delete_provider(): void
    {
        $user = User::factory()->create();
        $provider = ImportProvider::factory()->for($user)->create(['name' => 'Old']);

        $this
            ->actingAs($user)
            ->put(route('providers.update', $provider), $this->validPayload())
            ->assertRedirect(route('providers.index'));

        $this->assertSame('BNP relevé courant', $provider->fresh()->name);

        $this
            ->actingAs($user)
            ->delete(route('providers.destroy', $provider))
            ->assertRedirect(route('providers.index'));

        $this->assertNull($provider->fresh());
    }

    public function test_user_cannot_update_another_users_provider(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $provider = ImportProvider::factory()->for($owner)->create();

        $this
            ->actingAs($intruder)
            ->put(route('providers.update', $provider), $this->validPayload())
            ->assertForbidden();
    }
}
