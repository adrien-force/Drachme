<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\SettlementPeriodMode;
use App\Models\Concerns\BelongsToUser;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property AccountType $type
 * @property string|null $logo_path
 * @property \Illuminate\Support\Carbon|string|null $transactions_max_date
 */
#[Fillable([
    'user_id',
    'name',
    'institution',
    'logo_path',
    'type',
    'settlement_account_id',
    'billing_day',
    'settlement_label_pattern',
    'settlement_period_mode',
    'initial_balance',
    'current_balance',
    'currency',
    'opened_at',
    'is_archived',
])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'settlement_period_mode' => SettlementPeriodMode::class,
            'initial_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'opened_at' => 'date',
            'is_archived' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Account>  $query
     * @return Builder<Account>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function settlementAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'settlement_account_id');
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<Position, $this>
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }
}
