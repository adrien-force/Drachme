import { Head, router } from '@inertiajs/react';
import { Check, Repeat, Trash2, X } from 'lucide-react';
import { useState } from 'react';

import { CategoryBadge } from '@/components/categories/category-badge';
import { CategorySelect } from '@/components/categories/category-select';
import { GlassPanel } from '@/components/glass-panel';
import { FadeIn } from '@/components/motion/fade-in';
import { RecurringFiltersPanel } from '@/components/recurring/recurring-filters-panel';
import { RecurringPagination } from '@/components/recurring/recurring-pagination';
import { RecurringSummaryCharts } from '@/components/recurring/recurring-summary-charts';
import { TransactionTypeBadge } from '@/components/transactions/transaction-type-badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatRecurringSignedAmount,
    recurringAmountClassName,
} from '@/lib/recurring-amount';
import { recurringIndexQuery } from '@/lib/recurring-index-query';
import { recurringFrequencyLabel } from '@/lib/recurring-frequency';
import { formatCurrency } from '@/lib/format-currency';
import type {
    ConfirmedRecurringPattern,
    RecurringIndexPageProps,
    RecurringSuggestionRecord,
} from '@/types/recurring.types';
import type { CategorySelectOption } from '@/types/category.types';

function SuggestionCard({
    suggestion,
    categoryOptions,
}: {
    suggestion: RecurringSuggestionRecord;
    categoryOptions: CategorySelectOption[];
}) {
    const { t } = useTranslation();
    const [categoryId, setCategoryId] = useState<number | null>(
        suggestion.suggested_category_id,
    );

    const confirm = () => {
        router.post(
            '/recurring/confirm',
            {
                label_pattern: suggestion.label_pattern,
                display_label: suggestion.display_label,
                expected_amount: suggestion.expected_amount,
                frequency: suggestion.frequency,
                transaction_type: suggestion.transaction_type,
                occurrence_count: suggestion.occurrence_count,
                account_id: suggestion.account_id,
                category_id: categoryId,
            },
            { preserveScroll: true },
        );
    };

    const dismiss = () => {
        router.post(
            '/recurring/dismiss',
            {
                label_pattern: suggestion.label_pattern,
                transaction_type: suggestion.transaction_type,
            },
            { preserveScroll: true },
        );
    };

    return (
        <div className="border-border/60 space-y-3 rounded-xl border p-4">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0 space-y-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="font-medium">{suggestion.display_label}</p>
                        <TransactionTypeBadge type={suggestion.transaction_type} />
                    </div>
                    <p className="text-sm">
                        <span
                            className={`font-mono font-medium tabular-nums ${recurringAmountClassName(suggestion.transaction_type)}`}
                        >
                            {formatRecurringSignedAmount(suggestion.signed_amount)}
                        </span>
                        <span className="text-muted-foreground">
                            {' '}
                            {t('recurring.per_period')} ·{' '}
                            {recurringFrequencyLabel(suggestion.frequency, t)} ·{' '}
                            {formatCurrency(Number.parseFloat(suggestion.monthly_amount), {
                                precise: true,
                            })}
                            {t('recurring.per_month')}
                        </span>
                    </p>
                    <p className="text-muted-foreground text-xs">
                        {t('recurring.occurrences', {
                            count: suggestion.occurrence_count,
                        })}{' '}
                        · {t('recurring.score', { score: suggestion.score })}
                    </p>
                </div>
                <div className="flex shrink-0 flex-wrap gap-2">
                    <Button type="button" size="sm" onClick={confirm}>
                        <Check className="mr-1 size-4" />
                        {t('recurring.confirm')}
                    </Button>
                    <Button type="button" size="sm" variant="outline" onClick={dismiss}>
                        <X className="mr-1 size-4" />
                        {t('recurring.dismiss')}
                    </Button>
                </div>
            </div>
            <div className="max-w-xs space-y-1.5">
                <CategorySelect
                    value={categoryId}
                    onChange={setCategoryId}
                    options={categoryOptions}
                    placeholder={t('recurring.choose_category')}
                />
            </div>
            {suggestion.samples.length > 0 ? (
                <ul className="text-muted-foreground space-y-1 text-xs">
                    {suggestion.samples.map((sample) => (
                        <li key={sample.id}>
                            {sample.date} · {sample.account_name} ·{' '}
                            <span
                                className={recurringAmountClassName(sample.type)}
                            >
                                {formatCurrency(Number.parseFloat(sample.amount), {
                                    precise: true,
                                })}
                            </span>
                        </li>
                    ))}
                </ul>
            ) : null}
        </div>
    );
}

function ConfirmedRow({ pattern }: { pattern: ConfirmedRecurringPattern }) {
    const { t } = useTranslation();

    const remove = () => {
        router.delete(`/recurring/${pattern.id}`, { preserveScroll: true });
    };

    return (
        <div className="border-border/60 flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="min-w-0 space-y-2">
                <div className="flex flex-wrap items-center gap-2">
                    <Repeat className="text-primary size-4 shrink-0" />
                    <p className="font-medium">{pattern.display_label}</p>
                    <TransactionTypeBadge type={pattern.transaction_type} />
                </div>
                <p className="text-sm">
                    <span
                        className={`font-mono font-medium tabular-nums ${recurringAmountClassName(pattern.transaction_type)}`}
                    >
                        {formatRecurringSignedAmount(pattern.signed_amount)}
                    </span>
                    <span className="text-muted-foreground">
                        {' '}
                        {t('recurring.per_period')} ·{' '}
                        {recurringFrequencyLabel(pattern.frequency, t)}
                        {pattern.account_name ? ` · ${pattern.account_name}` : ''}
                        {' · '}
                        {formatCurrency(Number.parseFloat(pattern.monthly_amount), {
                            precise: true,
                        })}
                        {t('recurring.per_month')}
                    </span>
                </p>
                {pattern.category_name ? (
                    <CategoryBadge
                        name={pattern.category_name}
                        color={pattern.category_color}
                    />
                ) : null}
                {pattern.last_seen_at ? (
                    <p className="text-muted-foreground text-xs">
                        {t('recurring.sort_last_seen')}: {pattern.last_seen_at}
                    </p>
                ) : null}
            </div>
            <Button type="button" size="sm" variant="outline" onClick={remove}>
                <Trash2 className="mr-1 size-4" />
                {t('recurring.remove')}
            </Button>
        </div>
    );
}

export default function RecurringIndex({
    suggestions,
    confirmed,
    summary,
    filters,
    perPageOptions,
    frequencyOptions,
    categoryOptions,
}: RecurringIndexPageProps) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('recurring.title')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {t('recurring.title')}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {t('recurring.description')}
                    </p>
                </div>

                <FadeIn>
                    <RecurringSummaryCharts summary={summary} />
                </FadeIn>

                <FadeIn delay={0.03}>
                    <RecurringFiltersPanel
                        filters={filters}
                        perPageOptions={perPageOptions}
                        frequencyOptions={frequencyOptions}
                    />
                </FadeIn>

                <FadeIn delay={0.05}>
                    <GlassPanel className="space-y-4 p-4 md:p-6">
                        <h2 className="text-lg font-semibold">
                            {t('recurring.confirmed_title')}
                        </h2>
                        {confirmed.data.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {t('recurring.confirmed_empty')}
                            </p>
                        ) : (
                            <>
                                <div className="flex flex-col gap-3">
                                    {confirmed.data.map((pattern) => (
                                        <ConfirmedRow key={pattern.id} pattern={pattern} />
                                    ))}
                                </div>
                                <RecurringPagination
                                    meta={confirmed.meta}
                                    onPageChange={(page) =>
                                        router.get(
                                            '/recurring',
                                            recurringIndexQuery(filters, {
                                                confirmed_page: page,
                                            }),
                                            { preserveScroll: true, preserveState: true },
                                        )
                                    }
                                />
                            </>
                        )}
                    </GlassPanel>
                </FadeIn>

                <FadeIn delay={0.08}>
                    <GlassPanel className="space-y-4 p-4 md:p-6">
                        <h2 className="text-lg font-semibold">
                            {t('recurring.suggestions_title')}
                        </h2>
                        {suggestions.data.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {t('recurring.suggestions_empty')}
                            </p>
                        ) : (
                            <>
                                <div className="flex flex-col gap-3">
                                    {suggestions.data.map((suggestion) => (
                                        <SuggestionCard
                                            key={`${suggestion.label_pattern}-${suggestion.transaction_type}`}
                                            suggestion={suggestion}
                                            categoryOptions={categoryOptions}
                                        />
                                    ))}
                                </div>
                                <RecurringPagination
                                    meta={suggestions.meta}
                                    onPageChange={(page) =>
                                        router.get(
                                            '/recurring',
                                            recurringIndexQuery(filters, {
                                                suggestions_page: page,
                                            }),
                                            { preserveScroll: true, preserveState: true },
                                        )
                                    }
                                />
                            </>
                        )}
                    </GlassPanel>
                </FadeIn>
            </div>
        </>
    );
}

RecurringIndex.layout = {
    breadcrumbs: [{ titleKey: 'nav.recurring', href: '/recurring' }],
};
