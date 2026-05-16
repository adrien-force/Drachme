<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImportBatchStatus;
use App\Models\Concerns\BelongsToUser;
use Database\Factories\ImportBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property ImportBatchStatus $status
 * @property Carbon|null $completed_at
 */
#[Fillable([
    'user_id',
    'import_provider_id',
    'account_id',
    'status',
    'original_filename',
    'stored_path',
    'preview_rows',
    'duplicate_decisions',
    'imported_count',
    'skipped_count',
    'replaced_count',
    'error_count',
    'completed_at',
])]
class ImportBatch extends Model
{
    /** @use HasFactory<ImportBatchFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ImportBatchStatus::class,
            'preview_rows' => 'array',
            'duplicate_decisions' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ImportProvider, $this>
     */
    public function importProvider(): BelongsTo
    {
        return $this->belongsTo(ImportProvider::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
