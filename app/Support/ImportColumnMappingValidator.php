<?php

namespace App\Support;

use App\Enums\ImportColumnField;
use Illuminate\Validation\Validator;

class ImportColumnMappingValidator
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

        if (! in_array(ImportColumnField::Date->value, $fields, true)) {
            $validator->errors()->add($attribute, __('validation.import_mapping_requires_date'));
        }

        if (! in_array(ImportColumnField::Label->value, $fields, true)) {
            $validator->errors()->add($attribute, __('validation.import_mapping_requires_label'));
        }

        $hasAmountSigned = in_array(ImportColumnField::AmountSigned->value, $fields, true);
        $hasDebit = in_array(ImportColumnField::Debit->value, $fields, true);
        $hasCredit = in_array(ImportColumnField::Credit->value, $fields, true);

        if (! $hasAmountSigned && ! $hasDebit && ! $hasCredit) {
            $validator->errors()->add($attribute, __('validation.import_mapping_requires_amount'));
        }
    }
}
