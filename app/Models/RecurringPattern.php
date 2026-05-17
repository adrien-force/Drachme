<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecurringFrequency;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringPattern extends Model
{
    use BelongsToUser;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'label_pattern',
        'display_label',
        'expected_amount',
        'frequency',
        'category_id',
        'account_id',
        'occurrence_count',
        'last_seen_at',
        'is_confirmed',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expected_amount' => 'decimal:2',
            'frequency' => RecurringFrequency::class,
            'last_seen_at' => 'date',
            'is_confirmed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
