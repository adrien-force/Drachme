import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

import {
    TransactionFormPanel,
    type TransactionFormData,
} from '@/components/transactions/transaction-form-panel';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslation } from '@/hooks/use-translation';
import type { TransactionsFormPageProps } from '@/types/transaction.types';

type TransactionEditModalProps = TransactionsFormPageProps & {
    open: boolean;
    onClose: () => void;
};

export function TransactionEditModal({
    open,
    onClose,
    transaction,
    accounts,
    typeOptions,
    categoryOptions,
    suggestedCategory,
}: TransactionEditModalProps) {
    const { t } = useTranslation();

    const form = useForm<TransactionFormData>({
        account_id: String(transaction?.account_id ?? ''),
        date: transaction?.date ?? new Date().toISOString().slice(0, 10),
        label: transaction?.label ?? '',
        amount: transaction ? String(transaction.amount) : '',
        type: transaction?.type ?? 'expense',
        notes: transaction?.notes ?? '',
        category_id: transaction?.category_id ?? null,
        apply_category_rules: false,
    });

    useEffect(() => {
        if (!open || transaction === null) {
            return;
        }

        form.setData({
            account_id: String(transaction.account_id),
            date: transaction.date,
            label: transaction.label,
            amount: String(transaction.amount),
            type: transaction.type,
            notes: transaction.notes ?? '',
            category_id: transaction.category_id,
            apply_category_rules: false,
        });
        form.clearErrors();
    }, [open, transaction?.id]);

    if (transaction === null || !open) {
        return null;
    }

    const submit = () => {
        const payload = {
            ...form.data,
            account_id: Number(form.data.account_id),
            type: form.data.type,
            category_id: form.data.category_id,
            apply_category_rules: form.data.apply_category_rules,
        };

        form.transform(() => payload);
        form.put(`/transactions/${transaction.id}`, {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent
                overlayClassName="bg-black/60 backdrop-blur-sm"
                className="bg-card border-border max-h-[min(90vh,820px)] overflow-y-auto shadow-xl sm:max-w-xl"
            >
                <DialogHeader>
                    <DialogTitle>{t('transactions.edit_title')}</DialogTitle>
                    <DialogDescription>
                        {t('transactions.edit_description')}
                    </DialogDescription>
                </DialogHeader>

                <TransactionFormPanel
                    form={form}
                    accounts={accounts}
                    typeOptions={typeOptions}
                    categoryOptions={categoryOptions}
                    suggestedCategory={suggestedCategory}
                    transaction={transaction}
                    isEditing
                    compact
                    onCancel={onClose}
                    onSubmit={submit}
                />
            </DialogContent>
        </Dialog>
    );
}
