<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ImportBatchStatus;
use App\Models\Account;
use App\Models\ImportBatch;
use App\Models\ImportProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportBatch>
 */
class ImportBatchFactory extends Factory
{
    protected $model = ImportBatch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'import_provider_id' => ImportProvider::factory(),
            'account_id' => Account::factory(),
            'status' => ImportBatchStatus::Draft,
            'original_filename' => null,
            'stored_path' => null,
            'preview_rows' => null,
            'duplicate_decisions' => null,
            'imported_count' => 0,
            'skipped_count' => 0,
            'replaced_count' => 0,
            'error_count' => 0,
            'completed_at' => null,
        ];
    }
}
