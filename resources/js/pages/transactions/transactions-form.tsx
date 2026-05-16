import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo } from 'react';

import { EntityLogo } from '@/components/entity-logo';
import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { TransactionTypeBadge } from '@/components/transactions/transaction-type-badge';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { TransactionType, TransactionsFormPageProps } from '@/types/transaction.types';

const TYPE_AUTO = 'auto';

function inferType(amount: number): TransactionType {
    if (amount < 0) {
        return 'expense';
    }

    if (amount > 0) {
        return 'income';
    }

    return 'transfer';
}

export default function TransactionsForm({
    transaction,
    accounts,
    presetAccountId,
    typeOptions,
}: TransactionsFormPageProps) {
    const { t } = useTranslation();
    const isEditing = transaction !== null;

    const form = useForm({
        account_id: String(
            transaction?.account_id ?? presetAccountId ?? accounts[0]?.id ?? '',
        ),
        date: transaction?.date ?? new Date().toISOString().slice(0, 10),
        label: transaction?.label ?? '',
        amount: transaction ? String(transaction.amount) : '',
        type: transaction?.type ?? TYPE_AUTO,
        notes: transaction?.notes ?? '',
    });

    const previewType = useMemo(() => {
        if (form.data.type !== TYPE_AUTO) {
            return form.data.type as TransactionType;
        }

        const amount = Number.parseFloat(form.data.amount);
        if (Number.isNaN(amount)) {
            return null;
        }

        return inferType(amount);
    }, [form.data.amount, form.data.type]);

    const submit = () => {
        const payload = {
            ...form.data,
            account_id: Number(form.data.account_id),
            type: form.data.type === TYPE_AUTO ? null : form.data.type,
        };

        if (isEditing && transaction) {
            form.transform(() => payload);
            form.put(`/transactions/${transaction.id}`, {
                preserveScroll: true,
            });

            return;
        }

        form.transform(() => payload);
        form.post('/transactions', { preserveScroll: true });
    };

    return (
        <>
            <Head
                title={
                    isEditing
                        ? t('transactions.edit_title')
                        : t('transactions.create_title')
                }
            />

            <div className="mx-auto flex w-full max-w-xl flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {isEditing
                            ? t('transactions.edit_title')
                            : t('transactions.create_title')}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {isEditing
                            ? t('transactions.edit_description')
                            : t('transactions.create_description')}
                    </p>
                </div>

                <FadeIn>
                    <GlassPanel className="space-y-6 p-6">
                        <div className="space-y-2">
                            <Label>{t('transactions.account')}</Label>
                            <Select
                                value={form.data.account_id}
                                onValueChange={(value) =>
                                    form.setData('account_id', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {accounts.map((account) => (
                                        <SelectItem
                                            key={account.id}
                                            value={String(account.id)}
                                        >
                                            <span className="flex items-center gap-2">
                                                <EntityLogo
                                                    name={account.name}
                                                    logoUrl={account.logo_url}
                                                    className="size-5"
                                                />
                                                {account.name}
                                            </span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.account_id} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="date">{t('transactions.date')}</Label>
                                <Input
                                    id="date"
                                    type="date"
                                    value={form.data.date}
                                    onChange={(event) =>
                                        form.setData('date', event.target.value)
                                    }
                                />
                                <InputError message={form.errors.date} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="amount">
                                    {t('transactions.amount')}
                                </Label>
                                <Input
                                    id="amount"
                                    type="number"
                                    step="0.01"
                                    value={form.data.amount}
                                    onChange={(event) =>
                                        form.setData('amount', event.target.value)
                                    }
                                />
                                <p className="text-muted-foreground text-xs">
                                    {t('transactions.amount_hint')}
                                </p>
                                <InputError message={form.errors.amount} />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="label">{t('transactions.label')}</Label>
                            <Input
                                id="label"
                                value={form.data.label}
                                onChange={(event) =>
                                    form.setData('label', event.target.value)
                                }
                            />
                            <InputError message={form.errors.label} />
                        </div>

                        <div className="space-y-2">
                            <Label>{t('transactions.type')}</Label>
                            <Select
                                value={form.data.type}
                                onValueChange={(value) => form.setData('type', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={TYPE_AUTO}>
                                        {t('transactions.type_auto')}
                                    </SelectItem>
                                    {typeOptions.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {previewType ? (
                                <div className="flex items-center gap-2 pt-1">
                                    <TransactionTypeBadge type={previewType} />
                                </div>
                            ) : null}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="notes">{t('transactions.notes')}</Label>
                            <textarea
                                id="notes"
                                rows={3}
                                value={form.data.notes}
                                onChange={(event) =>
                                    form.setData('notes', event.target.value)
                                }
                                className={cn(
                                    'border-input placeholder:text-muted-foreground w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none',
                                    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                                )}
                            />
                            <InputError message={form.errors.notes} />
                        </div>

                        {'transaction' in form.errors ? (
                            <InputError
                                message={
                                    (form.errors as Record<string, string>)
                                        .transaction
                                }
                            />
                        ) : null}

                        <div className="flex flex-wrap gap-3">
                            <Button
                                type="button"
                                disabled={form.processing}
                                onClick={submit}
                            >
                                {t('transactions.save')}
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link
                                    href={
                                        form.data.account_id
                                            ? `/accounts/${form.data.account_id}`
                                            : '/accounts'
                                    }
                                >
                                    {t('transactions.cancel')}
                                </Link>
                            </Button>
                        </div>
                    </GlassPanel>
                </FadeIn>
            </div>
        </>
    );
}

TransactionsForm.layout = {
    breadcrumbs: [
        { title: 'Transactions', href: '/transactions' },
    ],
};
