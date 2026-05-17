<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ImportPositionColumnField;
use Illuminate\Contracts\Validation\Validator;

class ImportPositionColumnMappingValidator
{
    /**
     * @param  array<string, mixed>  $mapping
     */
    public static function validate(array $mapping, Validator $validator, string $attribute = 'column_mapping'): void
    {
        $columns = $mapping['columns'] ?? null;

        if (! is_array($columns)) {
            return;
        }

        $fields = [];

        foreach ($columns as $column) {
            if (! is_array($column)) {
                continue;
            }

            $field = $column['field'] ?? null;

            if (is_string($field)) {
                $fields[] = $field;
            }
        }

        if (! in_array(ImportPositionColumnField::Isin->value, $fields, true)) {
            $validator->errors()->add($attribute, __('validation.import_mapping_requires_isin'));
        }

        if (! in_array(ImportPositionColumnField::Quantity->value, $fields, true)) {
            $validator->errors()->add($attribute, __('validation.import_mapping_requires_quantity'));
        }

        $hasAverage = in_array(ImportPositionColumnField::AveragePrice->value, $fields, true);
        $hasLast = in_array(ImportPositionColumnField::LastPrice->value, $fields, true);

        if (! $hasAverage && ! $hasLast) {
            $validator->errors()->add($attribute, __('validation.import_mapping_requires_position_price'));
        }
    }
}
