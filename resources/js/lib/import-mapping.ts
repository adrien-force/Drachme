import type { ColumnMappingEntry, ImportColumnField } from '@/types/provider.types';

export function buildColumnMappings(
    columnCount: number,
    existing: ColumnMappingEntry[],
): ColumnMappingEntry[] {
    return Array.from({ length: columnCount }, (_, index) => {
        const found = existing.find((column) => column.index === index);

        return {
            index,
            field: found?.field ?? 'skip',
        };
    });
}

export function isImportMappingValid(columns: ColumnMappingEntry[]): boolean {
    const fields = columns.map((column) => column.field).filter((field) => field !== 'skip');

    const hasDate = fields.includes('date');
    const hasLabel = fields.includes('label');
    const hasAmount =
        fields.includes('amount_signed') ||
        fields.includes('debit') ||
        fields.includes('credit');

    return hasDate && hasLabel && hasAmount;
}

export function getDateColumnIndex(columns: ColumnMappingEntry[]): number | null {
    const dateColumn = columns.find((column) => column.field === 'date');

    return dateColumn?.index ?? null;
}

export function extractDateSamples(
    rows: string[][],
    dateColumnIndex: number | null,
): string[] {
    if (dateColumnIndex === null) {
        return [];
    }

    return rows
        .map((row) => row[dateColumnIndex]?.trim() ?? '')
        .filter((value) => value.length > 0)
        .slice(0, 15);
}

export function mappingPayload(columns: ColumnMappingEntry[]): {
    columns: ColumnMappingEntry[];
} {
    return {
        columns: columns.map((column) => ({
            index: column.index,
            field: column.field as ImportColumnField,
        })),
    };
}
