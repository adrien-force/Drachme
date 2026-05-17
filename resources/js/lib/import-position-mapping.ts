import { guessTransactionFieldFromHeader } from '@/lib/import-mapping';
import type {
    ColumnMappingEntry,
    ColumnMappingField,
    ImportPositionColumnField,
    ImportProviderType,
} from '@/types/provider.types';

function normalizeHeader(value: string): string {
    return value.trim().toLowerCase().replace(/[^a-z0-9]/g, '');
}

export function guessPositionFieldFromHeader(header: string): ImportPositionColumnField {
    const key = normalizeHeader(header);

    if (key.includes('isin')) {
        return 'isin';
    }

    if (
        key === 'name' ||
        key.includes('libelle') ||
        key.includes('label') ||
        key.includes('titre') ||
        key.includes('title')
    ) {
        return 'position_label';
    }

    if (key.includes('quantity') || key.includes('quantite') || key === 'qty') {
        return 'quantity';
    }

    if (
        key.includes('buyingprice') ||
        key.includes('prixachat') ||
        key.includes('pru') ||
        key.includes('average') ||
        key.includes('cost')
    ) {
        return 'average_price';
    }

    if (
        key.includes('lastprice') ||
        key.includes('cours') ||
        key.includes('price') ||
        key.includes('prix')
    ) {
        return 'last_price';
    }

    return 'skip';
}

export function isPositionMappingValid(columns: ColumnMappingEntry[]): boolean {
    const fields = columns.map((column) => column.field).filter((field) => field !== 'skip');

    const hasIsin = fields.includes('isin');
    const hasQuantity = fields.includes('quantity');
    const hasPrice =
        fields.includes('average_price') || fields.includes('last_price');

    return hasIsin && hasQuantity && hasPrice;
}

export function isMappingValidForType(
    columns: ColumnMappingEntry[],
    importType: ImportProviderType,
): boolean {
    if (importType === 'positions') {
        return isPositionMappingValid(columns);
    }

    const fields = columns.map((column) => column.field).filter((field) => field !== 'skip');

    const hasDate = fields.includes('date');
    const hasLabel = fields.includes('label');
    const hasAmount =
        fields.includes('amount_signed') ||
        fields.includes('debit') ||
        fields.includes('credit');

    return hasDate && hasLabel && hasAmount;
}

export function buildColumnMappingsForType(
    columnCount: number,
    existing: ColumnMappingEntry[],
    importType: ImportProviderType,
    headerCells?: string[],
): ColumnMappingEntry[] {
    return Array.from({ length: columnCount }, (_, index) => {
        const found = existing.find((column) => column.index === index);

        if (found && found.field !== 'skip') {
            return found;
        }

        if (headerCells?.[index]) {
            const guessed: ColumnMappingField =
                importType === 'positions'
                    ? guessPositionFieldFromHeader(headerCells[index])
                    : guessTransactionFieldFromHeader(headerCells[index]);

            if (guessed !== 'skip') {
                return { index, field: guessed };
            }
        }

        return {
            index,
            field: (found?.field ?? 'skip') as ColumnMappingField,
        };
    });
}

export function rowLooksLikePositionHeader(row: string[]): boolean {
    const normalized = row.map((cell) => normalizeHeader(cell ?? ''));

    return normalized.some((cell) => cell.includes('isin'));
}
