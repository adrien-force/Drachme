<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionType;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\SelectsEncryptedAttributes;
use App\Support\TransactionLabelIndex;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;
use ParagonIE\CipherSweet\Transformation\Lowercase;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;

#[Fillable([
    'user_id',
    'account_id',
    'date',
    'label',
    'amount',
    'type',
    'category_id',
    'transfer_pair_id',
    'is_card_settlement',
    'card_period_start',
    'import_batch_id',
    'import_hash',
    'notes',
])]
class Transaction extends Model implements CipherSweetEncrypted
{
    /** @use HasFactory<TransactionFactory> */
    use BelongsToUser, HasFactory, SelectsEncryptedAttributes, UsesCipherSweet;

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addTextField('label')
            ->addBlindIndex(
                'label',
                new BlindIndex(TransactionLabelIndex::BLIND_INDEX_NAME, [new Lowercase()]),
            )
            ->addOptionalTextField('notes');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'type' => TransactionType::class,
            'is_card_settlement' => 'boolean',
            'card_period_start' => 'date',
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

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<TransactionLabelToken, $this>
     */
    public function labelTokens(): HasMany
    {
        return $this->hasMany(TransactionLabelToken::class);
    }
}
