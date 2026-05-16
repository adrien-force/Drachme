<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionType;
use App\Models\Concerns\BelongsToUser;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'account_id',
    'date',
    'label',
    'amount',
    'type',
    'category_id',
    'transfer_pair_id',
    'import_batch_id',
    'import_hash',
    'notes',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'type' => TransactionType::class,
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

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transferPair(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transfer_pair_id');
    }
}
