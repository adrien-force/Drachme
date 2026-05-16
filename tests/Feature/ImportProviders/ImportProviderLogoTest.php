<?php

declare(strict_types=1);


namespace Tests\Feature\ImportProviders;

use App\Enums\ImportColumnField;
use App\Models\ImportProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportProviderLogoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'BNP CSV',
            'column_mapping' => [
                'columns' => [
                    ['index' => 0, 'field' => ImportColumnField::Date->value],
                    ['index' => 1, 'field' => ImportColumnField::Label->value],
                    ['index' => 2, 'field' => ImportColumnField::AmountSigned->value],
                ],
            ],
        ];
    }

    public function test_user_can_upload_provider_logo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('providers.store'), [
                ...$this->validPayload(),
                'logo' => UploadedFile::fake()->image('bank.png', 64, 64),
            ])
            ->assertRedirect(route('providers.index'));

        $provider = ImportProvider::query()->first();
        $this->assertNotNull($provider);
        $this->assertNotNull($provider->logo_path);
        Storage::disk('public')->assertExists($provider->logo_path);
    }
}
