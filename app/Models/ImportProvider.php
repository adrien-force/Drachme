<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImportProviderType;
use App\Models\Concerns\BelongsToUser;
use Database\Factories\ImportProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ImportProviderType $import_type
 * @property array<string, mixed> $column_mapping
 * @property array<string, mixed> $csv_options
 */
#[Fillable([
    'user_id',
    'name',
    'default_account_id',
    'import_type',
    'column_mapping',
    'csv_options',
])]
class ImportProvider extends Model
{
    /** @use HasFactory<ImportProviderFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'import_type' => ImportProviderType::class,
            'column_mapping' => 'array',
            'csv_options' => 'array',
        ];
    }

    public function isPositionsImport(): bool
    {
        return $this->import_type === ImportProviderType::Positions;
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function defaultAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_account_id');
    }
}
