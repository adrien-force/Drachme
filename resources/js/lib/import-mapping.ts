import type {
    ColumnMappingEntry,
    ColumnMappingField,
    ImportColumnField,
} from '@/types/provider.types';

function normalizeHeader(value: string): string {
    return value.trim().toLowerCase().replace(/[^a-z0-9]/g, '');
}

export function guessTransactionFieldFromHeader(header: string): ImportColumnField {
    const key = normalizeHeader(header);

    if (key.includes('date') || key === 'jour') {
        return 'date';
    }

    if (
        key.includes('libelle') ||
        key.includes('label') ||
        key.includes('description') ||
        key.includes('wording')
    ) {
        return 'label';
    }

    if (key.includes('debit')) {
        return 'debit';
    }

    if (key.includes('credit')) {
        return 'credit';
    }

    if (key.includes('solde') || key.includes('balance')) {
        return 'balance';
    }

    if (
        key.includes('montant') ||
        key.includes('amount') ||
        key.includes('somme')
    ) {
        return 'amount_signed';
    }

    return 'skip';
}

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
            field: column.field,
        })),
    };
}
