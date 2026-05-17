<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $isin
 * @property string $label
 * @property string $quantity
 * @property string $average_price
 * @property string|null $last_price
 * @property \Illuminate\Support\Carbon|null $last_price_at
 */
#[Fillable([
    'user_id',
    'account_id',
    'isin',
    'label',
    'quantity',
    'average_price',
    'last_price',
    'last_price_at',
])]
class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'average_price' => 'decimal:6',
            'last_price' => 'decimal:6',
            'last_price_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
