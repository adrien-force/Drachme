import { router } from '@inertiajs/react';
import { Repeat } from 'lucide-react';
import { useState } from 'react';

import { RecurringBadge } from '@/components/recurring/recurring-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import {
    RECURRING_FREQUENCIES,
    recurringFrequencyLabel,
} from '@/lib/recurring-frequency';
import type { RecurringFrequency } from '@/types/recurring.types';
import type { TransactionListItem } from '@/types/transaction.types';

type TransactionRecurringDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    transaction: TransactionListItem;
};

export function TransactionRecurringDialog({
    open,
    onOpenChange,
    transaction,
}: TransactionRecurringDialogProps) {
    const { t } = useTranslation();
    const [frequency, setFrequency] = useState<RecurringFrequency>('monthly');

    const isRecurring = transaction.recurring_pattern_id != null;

    const markRecurring = () => {
        router.post(
            `/transactions/${transaction.id}/recurring`,
            { frequency },
            { preserveScroll: true, onSuccess: () => onOpenChange(false) },
        );
    };

    const unmarkRecurring = () => {
        router.delete(`/transactions/${transaction.id}/recurring`, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>{t('transactions.recurring_title')}</DialogTitle>
                    <DialogDescription>{t('transactions.recurring_hint')}</DialogDescription>
                </DialogHeader>

                {isRecurring ? (
                    <div className="space-y-3">
                        <RecurringBadge
                            label={transaction.recurring_display_label ?? transaction.label}
                        />
                    </div>
                ) : (
                    <div className="space-y-2">
                        <Label htmlFor={`recurring-frequency-${transaction.id}`}>
                            {t('transactions.recurring_frequency')}
                        </Label>
                        <Select
                            value={frequency}
                            onValueChange={(value) =>
                                setFrequency(value as RecurringFrequency)
                            }
                        >
                            <SelectTrigger id={`recurring-frequency-${transaction.id}`}>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {RECURRING_FREQUENCIES.map((option) => (
                                    <SelectItem key={option} value={option}>
                                        {recurringFrequencyLabel(option, t)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}

                <DialogFooter>
                    <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                        {t('transactions.cancel')}
                    </Button>
                    {isRecurring ? (
                        <Button type="button" variant="destructive" onClick={unmarkRecurring}>
                            {t('transactions.unmark_recurring')}
                        </Button>
                    ) : (
                        <Button type="button" onClick={markRecurring}>
                            <Repeat className="mr-1 size-4" />
                            {t('transactions.mark_recurring')}
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
