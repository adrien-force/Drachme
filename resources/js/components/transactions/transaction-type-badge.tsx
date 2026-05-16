import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { TransactionType } from '@/types/transaction.types';

const styles: Record<TransactionType, string> = {
    expense: 'bg-destructive/15 text-destructive',
    income: 'bg-emerald-500/15 text-emerald-400',
    transfer: 'bg-primary/15 text-primary',
};

export function TransactionTypeBadge({ type }: { type: TransactionType }) {
    const { t } = useTranslation();

    return (
        <span
            className={cn(
                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                styles[type],
            )}
        >
            {t(`transactions.types.${type}`)}
        </span>
    );
}
