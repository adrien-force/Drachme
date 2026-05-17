<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DismissedTransferSuggestion extends Model
{
    use BelongsToUser;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'transaction_a_id',
        'transaction_b_id',
    ];

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transactionA(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_a_id');
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transactionB(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_b_id');
    }

    /**
     * @return array{0: int, 1: int}
     */
    public static function canonicalPairIds(int $firstId, int $secondId): array
    {
        return $firstId < $secondId
            ? [$firstId, $secondId]
            : [$secondId, $firstId];
    }
}
