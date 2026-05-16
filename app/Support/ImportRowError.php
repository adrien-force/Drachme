<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ImportColumnField;

class ImportRowError
{
    public static function linePrefix(int $line): string
    {
        return (string) __('ui.providers.errors.line_prefix', ['line' => $line]);
    }

    public static function wrapLine(int $line, string $message): string
    {
        return self::linePrefix($line).': '.$message;
    }

    public static function dateEmpty(): string
    {
        return (string) __('ui.providers.errors.date_empty', [
            'field' => self::fieldLabel(ImportColumnField::Date),
        ]);
    }

    public static function dateFormatMismatch(string $value, string $format): string
    {
        $message = (string) __('ui.providers.errors.date_format_mismatch', [
            'value' => $value,
            'format' => $format,
        ]);

        if (self::formatExpectsTime($format) && ! self::valueHasTime($value)) {
            $message .= ' '.(string) __('ui.providers.errors.date_missing_time_hint', [
                'example' => self::exampleDateTime($format),
            ]);
        }

        return $message;
    }

    public static function dateParseFailed(string $value, string $format): string
    {
        return (string) __('ui.providers.errors.date_parse_failed', [
            'value' => $value,
            'format' => $format,
        ]);
    }

    public static function labelEmpty(): string
    {
        return (string) __('ui.providers.errors.label_empty', [
            'field' => self::fieldLabel(ImportColumnField::Label),
        ]);
    }

    public static function fieldNotMapped(ImportColumnField $field): string
    {
        return (string) __('ui.providers.errors.field_not_mapped', [
            'field' => self::fieldLabel($field),
        ]);
    }

    /**
     * @param  array<string, string>  $fields
     */
    public static function amountMissing(array $fields): string
    {
        return (string) __('ui.providers.errors.amount_missing', [
            'signed' => $fields[ImportColumnField::AmountSigned->value] ?? '—',
            'debit' => $fields[ImportColumnField::Debit->value] ?? '—',
            'credit' => $fields[ImportColumnField::Credit->value] ?? '—',
        ]);
    }

    public static function amountSignedEmpty(): string
    {
        return (string) __('ui.providers.errors.amount_signed_empty', [
            'field' => self::fieldLabel(ImportColumnField::AmountSigned),
        ]);
    }

    public static function amountSignedInvalid(string $value): string
    {
        return (string) __('ui.providers.errors.amount_signed_invalid', [
            'value' => $value,
            'field' => self::fieldLabel(ImportColumnField::AmountSigned),
        ]);
    }

    private static function fieldLabel(ImportColumnField $field): string
    {
        return (string) __("ui.providers.fields.{$field->value}");
    }

    private static function formatExpectsTime(string $format): bool
    {
        return str_contains($format, 'H') || str_contains($format, 'h');
    }

    private static function valueHasTime(string $value): bool
    {
        return (bool) preg_match('/\d{1,2}:\d{2}/', $value);
    }

    private static function exampleDateTime(string $format): string
    {
        if (str_starts_with($format, 'Y-m-d')) {
            return '2022-07-22 01:34:51';
        }

        if (str_starts_with($format, 'd/m/Y')) {
            return '22/07/2022 01:34:51';
        }

        return '2022-07-22 01:34:51';
    }
}
