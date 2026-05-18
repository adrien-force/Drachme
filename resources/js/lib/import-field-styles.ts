import type { ColumnMappingField } from '@/types/provider.types';

const FIELD_HEADER_CLASSES: Record<ColumnMappingField, string> = {
    date: 'bg-chart-income/15 text-chart-income border-chart-income/30',
    label: 'bg-primary/10 text-primary border-primary/25',
    amount_signed: 'bg-chart-expense/10 text-foreground border-chart-expense/25',
    debit: 'bg-chart-expense/15 text-chart-expense border-chart-expense/30',
    credit: 'bg-chart-income/15 text-chart-income border-chart-income/30',
    balance: 'bg-muted text-muted-foreground border-border',
    skip: 'bg-muted/40 text-muted-foreground border-transparent',
    position_label: 'bg-primary/10 text-primary border-primary/25',
    isin: 'bg-violet-500/15 text-violet-700 dark:text-violet-300 border-violet-500/30',
    market_symbol: 'bg-indigo-500/15 text-indigo-800 dark:text-indigo-200 border-indigo-500/30',
    quantity: 'bg-sky-500/15 text-sky-800 dark:text-sky-200 border-sky-500/30',
    average_price: 'bg-amber-500/15 text-amber-900 dark:text-amber-100 border-amber-500/30',
    last_price: 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-200 border-emerald-500/30',
};

const FIELD_CELL_CLASSES: Record<ColumnMappingField, string> = {
    date: 'bg-chart-income/5',
    label: 'bg-primary/5',
    amount_signed: 'bg-chart-expense/5',
    debit: 'bg-chart-expense/5',
    credit: 'bg-chart-income/5',
    balance: 'bg-muted/30',
    skip: '',
    position_label: 'bg-primary/5',
    isin: 'bg-violet-500/5',
    market_symbol: 'bg-indigo-500/5',
    quantity: 'bg-sky-500/5',
    average_price: 'bg-amber-500/5',
    last_price: 'bg-emerald-500/5',
};

export function importFieldHeaderClass(field: ColumnMappingField): string {
    return FIELD_HEADER_CLASSES[field];
}

export function importFieldCellClass(field: ColumnMappingField): string {
    return FIELD_CELL_CLASSES[field];
}

export function columnLetter(index: number): string {
    let n = index;
    let label = '';

    do {
        label = String.fromCharCode(65 + (n % 26)) + label;
        n = Math.floor(n / 26) - 1;
    } while (n >= 0);

    return label;
}
