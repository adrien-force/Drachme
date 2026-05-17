<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property \Carbon\CarbonImmutable|\Illuminate\Support\Carbon|string $imported_at
 * @property array<int, array<string, mixed>> $lines
 */
class PortfolioSnapshot extends Model
{
    use BelongsToUser;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'account_id',
        'import_batch_id',
        'imported_at',
        'file_signature',
        'original_filename',
        'total_market_value',
        'positions_count',
        'lines',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
            'total_market_value' => 'decimal:2',
            'positions_count' => 'integer',
            'lines' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<ImportBatch, $this>
     */
    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }
}
