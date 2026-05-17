import { formatCurrency } from '@/lib/format-currency';
import type { TransactionType } from '@/types/transaction.types';

export function formatRecurringSignedAmount(signedAmount: string): string {
    return formatCurrency(Number.parseFloat(signedAmount), { precise: true });
}

export function recurringAmountClassName(transactionType: TransactionType): string {
    return transactionType === 'income' ? 'text-chart-income' : 'text-chart-expense';
}
