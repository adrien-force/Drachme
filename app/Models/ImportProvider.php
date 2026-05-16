<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Database\Factories\ImportProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'name',
    'default_account_id',
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
            'column_mapping' => 'array',
            'csv_options' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function defaultAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_account_id');
    }
}
