<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Transaction;
use ParagonIE\CipherSweet\Exception\BlindIndexNotFoundException;
use ParagonIE\CipherSweet\Exception\CipherSweetException;

final class TransactionLabelIndex
{
    public const string BLIND_INDEX_NAME = 'label_token';

    private const int MIN_TOKEN_LENGTH = 2;

    /**
     * @return list<string>
     */
    public function tokenizeSearch(string $search): array
    {
        $tokens = [];
        foreach (LabelTokenizer::tokenize($search) as $token) {
            if (mb_strlen($token) >= self::MIN_TOKEN_LENGTH) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    /**
     * @return list<string>
     */
    public function tokenizeLabel(string $label): array
    {
        return $this->tokenizeSearch($label);
    }

    /**
     * @throws BlindIndexNotFoundException
     * @throws CipherSweetException
     */
    public function hashToken(string $token): string
    {
        $normalized = mb_strtolower(trim($token));
        if ($normalized === '') {
            throw new CipherSweetException('Cannot hash an empty label token.');
        }

        $index = Transaction::getCipherSweetEncryptedRow()->getBlindIndex(
            self::BLIND_INDEX_NAME,
            ['label' => $normalized],
        );

        if (is_string($index)) {
            return $index;
        }

        $value = $index[self::BLIND_INDEX_NAME] ?? null;

        if (! is_string($value) || $value === '') {
            throw new CipherSweetException('Unexpected blind index shape for label token.');
        }

        return $value;
    }
}
