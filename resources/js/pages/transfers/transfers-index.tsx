import { Head, router, useForm } from '@inertiajs/react';
import { ArrowRight, Check, X } from 'lucide-react';

import { EntityLogo } from '@/components/entity-logo';
import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
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
import { formatCurrency } from '@/lib/format-currency';
import type { TransfersIndexPageProps } from '@/types/transfer.types';

function SuggestionRow({
    suggestion,
}: {
    suggestion: TransfersIndexPageProps['suggestions'][number];
}) {
    const { t } = useTranslation();

    const accept = () => {
        router.post('/transfers/accept', {
            outgoing_transaction_id: suggestion.outgoing.id,
            incoming_transaction_id: suggestion.incoming.id,
        });
    };

    const dismiss = () => {
        router.post('/transfers/dismiss', {
            outgoing_transaction_id: suggestion.outgoing.id,
            incoming_transaction_id: suggestion.incoming.id,
        });
    };

    return (
        <div className="border-border/60 flex flex-col gap-3 rounded-xl border p-4 lg:flex-row lg:items-center">
            <div className="grid min-w-0 flex-1 gap-3 sm:grid-cols-[1fr_auto_1fr] sm:items-center">
                <TransactionSide transaction={suggestion.outgoing} />
                <ArrowRight className="text-muted-foreground mx-auto size-5 shrink-0" />
                <TransactionSide transaction={suggestion.incoming} />
            </div>
            <div className="flex flex-wrap items-center gap-2 lg:shrink-0">
                <span className="text-muted-foreground text-xs">
                    {t('transfers.score', { score: suggestion.score })}
                </span>
                <Button type="button" size="sm" onClick={accept}>
                    <Check className="mr-1 size-4" />
                    {t('transfers.accept')}
                </Button>
                <Button type="button" size="sm" variant="outline" onClick={dismiss}>
                    <X className="mr-1 size-4" />
                    {t('transfers.dismiss')}
                </Button>
            </div>
        </div>
    );
}

function TransactionSide({
    transaction,
}: {
    transaction: TransfersIndexPageProps['suggestions'][number]['outgoing'];
}) {
    return (
        <div className="min-w-0 space-y-1">
            <div className="flex items-center gap-2">
                <EntityLogo
                    name={transaction.account_name}
                    logoUrl={transaction.account_logo_url}
                    className="size-6"
                />
                <span className="truncate font-medium">{transaction.account_name}</span>
            </div>
            <p className="truncate text-sm">{transaction.label}</p>
            <p className="text-muted-foreground text-xs">{transaction.date}</p>
            <p className="tabular-nums text-sm font-medium">
                {formatCurrency(Number.parseFloat(transaction.amount), { precise: true })}
            </p>
        </div>
    );
}

export default function TransfersIndex({
    suggestions,
    accountOptions,
}: TransfersIndexPageProps) {
    const { t } = useTranslation();

    const form = useForm({
        from_account_id: String(accountOptions[0]?.id ?? ''),
        to_account_id: String(accountOptions[1]?.id ?? accountOptions[0]?.id ?? ''),
        date: new Date().toISOString().slice(0, 10),
        label: '',
        amount: '',
        notes: '',
    });

    const submitManual = () => {
        form.post('/transfers', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('label', 'amount', 'notes');
            },
        });
    };

    return (
        <>
            <Head title={t('transfers.title')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {t('transfers.title')}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {t('transfers.description')}
                    </p>
                </div>

                <FadeIn>
                    <GlassPanel className="space-y-4 p-4 md:p-6">
                        <h2 className="text-lg font-semibold">
                            {t('transfers.suggestions_title')}
                        </h2>
                        {suggestions.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {t('transfers.suggestions_empty')}
                            </p>
                        ) : (
                            <div className="flex flex-col gap-3">
                                {suggestions.map((suggestion) => (
                                    <SuggestionRow
                                        key={`${suggestion.outgoing.id}-${suggestion.incoming.id}`}
                                        suggestion={suggestion}
                                    />
                                ))}
                            </div>
                        )}
                    </GlassPanel>
                </FadeIn>

                <FadeIn delay={0.05}>
                    <GlassPanel className="space-y-4 p-4 md:p-6">
                        <h2 className="text-lg font-semibold">{t('transfers.manual_title')}</h2>
                        <form
                            className="grid gap-4 sm:grid-cols-2"
                            onSubmit={(event) => {
                                event.preventDefault();
                                submitManual();
                            }}
                        >
                            <div className="space-y-2">
                                <Label htmlFor="transfer-from">
                                    {t('transfers.from_account')}
                                </Label>
                                <Select
                                    value={form.data.from_account_id}
                                    onValueChange={(value) =>
                                        form.setData('from_account_id', value)
                                    }
                                >
                                    <SelectTrigger id="transfer-from">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {accountOptions.map((account) => (
                                            <SelectItem
                                                key={account.id}
                                                value={String(account.id)}
                                            >
                                                {account.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.from_account_id} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="transfer-to">{t('transfers.to_account')}</Label>
                                <Select
                                    value={form.data.to_account_id}
                                    onValueChange={(value) =>
                                        form.setData('to_account_id', value)
                                    }
                                >
                                    <SelectTrigger id="transfer-to">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {accountOptions.map((account) => (
                                            <SelectItem
                                                key={account.id}
                                                value={String(account.id)}
                                            >
                                                {account.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.to_account_id} />
                            </div>

                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="transfer-date">{t('transactions.date')}</Label>
                                <DatePicker
                                    id="transfer-date"
                                    value={form.data.date || null}
                                    onChange={(date) =>
                                        form.setData('date', date ?? '')
                                    }
                                />
                                <InputError message={form.errors.date} />
                            </div>

                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="transfer-label">{t('transactions.label')}</Label>
                                <Input
                                    id="transfer-label"
                                    value={form.data.label}
                                    onChange={(event) =>
                                        form.setData('label', event.target.value)
                                    }
                                />
                                <InputError message={form.errors.label} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="transfer-amount">{t('transactions.amount')}</Label>
                                <Input
                                    id="transfer-amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    value={form.data.amount}
                                    onChange={(event) =>
                                        form.setData('amount', event.target.value)
                                    }
                                />
                                <p className="text-muted-foreground text-xs">
                                    {t('transfers.amount_hint')}
                                </p>
                                <InputError message={form.errors.amount} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="transfer-notes">{t('transactions.notes')}</Label>
                                <Input
                                    id="transfer-notes"
                                    value={form.data.notes}
                                    onChange={(event) =>
                                        form.setData('notes', event.target.value)
                                    }
                                />
                                <InputError message={form.errors.notes} />
                            </div>

                            <InputError
                                message={
                                    'transfer' in form.errors
                                        ? String(form.errors.transfer)
                                        : undefined
                                }
                            />

                            <div className="sm:col-span-2">
                                <Button type="submit" disabled={form.processing}>
                                    {t('transfers.create')}
                                </Button>
                            </div>
                        </form>
                    </GlassPanel>
                </FadeIn>
            </div>
        </>
    );
}

TransfersIndex.layout = {
    breadcrumbs: [{ title: 'Virements', href: '/transfers' }],
};
