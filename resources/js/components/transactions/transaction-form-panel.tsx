import { Repeat, Tags } from 'lucide-react';
import { useMemo, useState } from 'react';

import { CreateRuleFromLabelDialog } from '@/components/category-rules/create-rule-from-label-dialog';
import { TransactionRecurringDialog } from '@/components/transactions/transaction-recurring-dialog';
import { CategoryBadge } from '@/components/categories/category-badge';
import { CategorySelect } from '@/components/categories/category-select';
import { EntityLogo } from '@/components/entity-logo';
import { TransactionTypeBadge } from '@/components/transactions/transaction-type-badge';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { cn } from '@/lib/utils';
import type { InertiaForm } from '@inertiajs/react';
import type { CategorySelectOption } from '@/types/category.types';
import type {
    TransactionAccountOption,
    TransactionCategorySummary,
    TransactionListItem,
    TransactionType,
    TransactionTypeOption,
} from '@/types/transaction.types';

const TYPE_AUTO = 'auto';

export type TransactionFormData = {
    account_id: string;
    date: string;
    label: string;
    amount: string;
    type: string;
    notes: string;
    category_id: number | null;
    apply_category_rules: boolean;
    is_card_settlement: boolean;
    card_period_start: string | null;
};

type TransactionFormPanelProps = {
    form: InertiaForm<TransactionFormData>;
    accounts: TransactionAccountOption[];
    typeOptions: TransactionTypeOption[];
    categoryOptions: CategorySelectOption[];
    suggestedCategory: TransactionCategorySummary | null;
    transaction: TransactionListItem | null;
    isEditing: boolean;
    onCancel: () => void;
    onSubmit: () => void;
    compact?: boolean;
};

function inferType(amount: number): TransactionType {
    if (amount < 0) {
        return 'expense';
    }

    if (amount > 0) {
        return 'income';
    }

    return 'transfer';
}

export function TransactionFormPanel({
    form,
    accounts,
    typeOptions,
    categoryOptions,
    suggestedCategory,
    transaction,
    isEditing,
    onCancel,
    onSubmit,
    compact = false,
}: TransactionFormPanelProps) {
    const { t } = useTranslation();
    const [ruleDialogOpen, setRuleDialogOpen] = useState(false);
    const [recurringDialogOpen, setRecurringDialogOpen] = useState(false);

    const selectedAccount = useMemo(
        () => accounts.find((account) => String(account.id) === form.data.account_id),
        [accounts, form.data.account_id],
    );

    const accountType =
        transaction?.account_type ?? selectedAccount?.type ?? null;
    const isCreditCardAccount = accountType === 'credit_card';
    const parsedAmount = Number.parseFloat(form.data.amount);
    const showCardSettlementFields =
        isCreditCardAccount && !Number.isNaN(parsedAmount) && parsedAmount > 0;

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

    return (
        <>
            <div className={cn('space-y-6', compact && 'space-y-5')}>
                <div className="space-y-2">
                    <Label>{t('transactions.account')}</Label>
                    {isEditing && transaction ? (
                        <div className="border-input bg-muted/30 flex items-center gap-2 rounded-md border px-3 py-2 text-sm">
                            <EntityLogo
                                name={transaction.account_name ?? ''}
                                logoUrl={transaction.account_logo_url ?? null}
                                className="size-5 shrink-0"
                            />
                            <span className="min-w-0 truncate font-medium">
                                {transaction.account_name ?? '—'}
                            </span>
                        </div>
                    ) : (
                        <>
                            <Select
                                value={form.data.account_id}
                                onValueChange={(value) => form.setData('account_id', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {accounts.map((account) => (
                                        <SelectItem key={account.id} value={String(account.id)}>
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
                        </>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="transaction-date">{t('transactions.date')}</Label>
                        <DatePicker
                            id="transaction-date"
                            value={form.data.date || null}
                            onChange={(date) => form.setData('date', date ?? '')}
                        />
                        <InputError message={form.errors.date} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="transaction-amount">{t('transactions.amount')}</Label>
                        <Input
                            id="transaction-amount"
                            type="number"
                            step="0.01"
                            value={form.data.amount}
                            onChange={(event) => form.setData('amount', event.target.value)}
                        />
                        <InputError message={form.errors.amount} />
                    </div>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="transaction-label">{t('transactions.label')}</Label>
                    <Input
                        id="transaction-label"
                        value={form.data.label}
                        onChange={(event) => form.setData('label', event.target.value)}
                    />
                    <InputError message={form.errors.label} />
                </div>

                <div className="space-y-2">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <Label>{t('transactions.category')}</Label>
                        {form.data.label.trim() !== '' ? (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="h-8 gap-1.5 px-2"
                                onClick={() => setRuleDialogOpen(true)}
                            >
                                <Tags className="size-3.5" />
                                {t('category_rules.create_from_label')}
                            </Button>
                        ) : null}
                    </div>
                    <CategorySelect
                        value={form.data.category_id}
                        onChange={(categoryId) => form.setData('category_id', categoryId)}
                        options={categoryOptions}
                        noneLabel={t('transactions.category_none')}
                    />
                    {suggestedCategory && form.data.category_id === null ? (
                        <div className="flex flex-wrap items-center gap-2 pt-1">
                            <span className="text-muted-foreground text-xs">
                                {t('transactions.suggested_category')}
                            </span>
                            <CategoryBadge
                                name={suggestedCategory.name}
                                color={suggestedCategory.color}
                            />
                            <Button
                                type="button"
                                variant="link"
                                size="sm"
                                className="h-auto px-0 text-xs"
                                onClick={() =>
                                    form.setData('category_id', suggestedCategory.id)
                                }
                            >
                                {t('transactions.apply_suggestion')}
                            </Button>
                        </div>
                    ) : null}
                    <InputError message={form.errors.category_id} />
                </div>

                {!isEditing ? (
                    <p className="text-muted-foreground text-xs">
                        {t('transactions.auto_rules_hint')}
                    </p>
                ) : form.data.category_id === null ? (
                    <p className="text-muted-foreground text-xs leading-relaxed">
                        {t('transactions.apply_rules_implicit')}
                    </p>
                ) : (
                    <p className="text-muted-foreground text-xs">
                        {t('transactions.manual_category_hint')}
                    </p>
                )}

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
                                <SelectItem key={option.value} value={option.value}>
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

                {showCardSettlementFields ? (
                    <div className="border-primary/30 bg-primary/5 space-y-4 rounded-lg border p-4">
                        <div className="space-y-1">
                            <p className="text-sm font-medium">
                                {t('transactions.card_settlement_title')}
                            </p>
                            <p className="text-muted-foreground text-xs leading-relaxed">
                                {t('transactions.card_settlement_hint')}
                            </p>
                        </div>
                        <label className="flex cursor-pointer items-start gap-3">
                            <Checkbox
                                checked={form.data.is_card_settlement}
                                onCheckedChange={(checked) => {
                                    const enabled = checked === true;
                                    form.setData((current) => ({
                                        ...current,
                                        is_card_settlement: enabled,
                                        card_period_start: enabled
                                            ? current.card_period_start
                                            : null,
                                    }));
                                }}
                            />
                            <span className="text-sm leading-snug">
                                {t('transactions.card_settlement_checkbox')}
                            </span>
                        </label>
                        {form.data.is_card_settlement ? (
                            <div className="space-y-2">
                                <Label>{t('transactions.card_period_start')}</Label>
                                <DatePicker
                                    value={form.data.card_period_start}
                                    onChange={(value) =>
                                        form.setData('card_period_start', value)
                                    }
                                />
                                <p className="text-muted-foreground text-xs">
                                    {t('transactions.card_period_start_hint')}
                                </p>
                                <InputError message={form.errors.card_period_start} />
                            </div>
                        ) : null}
                        <InputError message={form.errors.is_card_settlement} />
                    </div>
                ) : null}

                {isEditing &&
                transaction &&
                !transaction.is_transfer_linked &&
                transaction.type !== 'transfer' ? (
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <div className="space-y-0.5">
                            <p className="text-sm font-medium">{t('transactions.recurring_title')}</p>
                            <p className="text-muted-foreground text-xs">
                                {t('transactions.recurring_manage_hint')}
                            </p>
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={() => setRecurringDialogOpen(true)}
                        >
                            <Repeat className="mr-1 size-4" />
                            {transaction.recurring_pattern_id != null
                                ? t('transactions.recurring_edit')
                                : t('transactions.recurring_set')}
                        </Button>
                    </div>
                ) : null}

                <div className="space-y-2">
                    <Label htmlFor="transaction-notes">{t('transactions.notes')}</Label>
                    <textarea
                        id="transaction-notes"
                        rows={3}
                        value={form.data.notes}
                        onChange={(event) => form.setData('notes', event.target.value)}
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
                            (form.errors as Record<string, string>).transaction
                        }
                    />
                ) : null}

                <div className="flex flex-wrap gap-3">
                    <Button type="button" disabled={form.processing} onClick={onSubmit}>
                        {t('transactions.save')}
                    </Button>
                    <Button type="button" variant="outline" onClick={onCancel}>
                        {t('transactions.cancel')}
                    </Button>
                </div>
            </div>

            <CreateRuleFromLabelDialog
                open={ruleDialogOpen}
                onOpenChange={setRuleDialogOpen}
                label={form.data.label}
                categoryOptions={categoryOptions}
                applyToTransactionId={transaction?.id ?? null}
            />

            {isEditing && transaction ? (
                <TransactionRecurringDialog
                    open={recurringDialogOpen}
                    onOpenChange={setRecurringDialogOpen}
                    transaction={transaction}
                />
            ) : null}
        </>
    );
}
