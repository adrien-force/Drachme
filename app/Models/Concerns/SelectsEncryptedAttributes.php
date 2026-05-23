<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait SelectsEncryptedAttributes
{
    /**
     * CipherSweet needs every encrypted column present (or permit_empty) when decrypting.
     *
     * @param  list<string>|array<int, string>  $columns
     * @return list<string>
     */
    public static function mergeEncryptedAttributeColumns(array $columns): array
    {
        if ($columns === ['*']) {
            return ['*'];
        }

        $encrypted = ['label', 'notes'];
        $needsNotes = array_intersect($encrypted, $columns) !== [];

        if ($needsNotes) {
            $columns = array_merge($columns, $encrypted);
        }

        return array_values(array_unique($columns));
    }
}
