import { Head, router } from '@inertiajs/react';
import { Check, Repeat, Trash2, X } from 'lucide-react';
import { useState } from 'react';

import { CategoryBadge } from '@/components/categories/category-badge';
import { CategorySelect } from '@/components/categories/category-select';
import { GlassPanel } from '@/components/glass-panel';
import { FadeIn } from '@/components/motion/fade-in';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import { recurringFrequencyLabel } from '@/lib/recurring-frequency';
import type {
    ConfirmedRecurringPattern,
    RecurringIndexPageProps,
    RecurringSuggestionRecord,
} from '@/types/recurring.types';

function SuggestionCard({
    suggestion,
    categoryOptions,
}: {
    suggestion: RecurringSuggestionRecord;
    categoryOptions: RecurringIndexPageProps['categoryOptions'];
}) {
    const { t } = useTranslation();
    const [categoryId, setCategoryId] = useState<number | null>(
        suggestion.suggested_category_id,
    );

    const confirm = () => {
        router.post('/recurring/confirm', {
            label_pattern: suggestion.label_pattern,
            display_label: suggestion.display_label,
            expected_amount: suggestion.expected_amount,
            frequency: suggestion.frequency,
            occurrence_count: suggestion.occurrence_count,
            account_id: suggestion.account_id,
            category_id: categoryId,
        });
    };

    const dismiss = () => {
        router.post('/recurring/dismiss', {
            label_pattern: suggestion.label_pattern,
        });
    };

    return (
        <div className="border-border/60 space-y-3 rounded-xl border p-4">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0 space-y-1">
                    <p className="font-medium">{suggestion.display_label}</p>
                    <p className="text-muted-foreground text-sm">
                        {formatCurrency(Number.parseFloat(suggestion.expected_amount), {
                            precise: true,
                        })}{' '}
                        · {recurringFrequencyLabel(suggestion.frequency, t)} ·{' '}
                        {t('recurring.occurrences', {
                            count: suggestion.occurrence_count,
                        })}
                    </p>
                    <p className="text-muted-foreground text-xs">
                        {t('recurring.score', { score: suggestion.score })}
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
                            {formatCurrency(Number.parseFloat(sample.amount), {
                                precise: true,
                            })}
                        </li>
                    ))}
                </ul>
            ) : null}
        </div>
    );
}

function ConfirmedCard({ pattern }: { pattern: ConfirmedRecurringPattern }) {
    const { t } = useTranslation();

    const remove = () => {
        router.delete(`/recurring/${pattern.id}`);
    };

    return (
        <div className="border-border/60 flex flex-col gap-2 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="min-w-0 space-y-1">
                <p className="flex items-center gap-2 font-medium">
                    <Repeat className="text-primary size-4 shrink-0" />
                    {pattern.display_label}
                </p>
                <p className="text-muted-foreground text-sm">
                    {formatCurrency(Number.parseFloat(pattern.expected_amount), {
                        precise: true,
                    })}{' '}
                    · {recurringFrequencyLabel(pattern.frequency, t)}
                    {pattern.account_name ? ` · ${pattern.account_name}` : ''}
                </p>
                {pattern.category_name ? (
                    <CategoryBadge
                        name={pattern.category_name}
                        color={pattern.category_color}
                    />
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
                    <GlassPanel className="space-y-4 p-4 md:p-6">
                        <h2 className="text-lg font-semibold">
                            {t('recurring.confirmed_title')}
                        </h2>
                        {confirmed.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {t('recurring.confirmed_empty')}
                            </p>
                        ) : (
                            <div className="flex flex-col gap-3">
                                {confirmed.map((pattern) => (
                                    <ConfirmedCard key={pattern.id} pattern={pattern} />
                                ))}
                            </div>
                        )}
                    </GlassPanel>
                </FadeIn>

                <FadeIn delay={0.05}>
                    <GlassPanel className="space-y-4 p-4 md:p-6">
                        <h2 className="text-lg font-semibold">
                            {t('recurring.suggestions_title')}
                        </h2>
                        {suggestions.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {t('recurring.suggestions_empty')}
                            </p>
                        ) : (
                            <div className="flex flex-col gap-3">
                                {suggestions.map((suggestion) => (
                                    <SuggestionCard
                                        key={suggestion.label_pattern}
                                        suggestion={suggestion}
                                        categoryOptions={categoryOptions}
                                    />
                                ))}
                            </div>
                        )}
                    </GlassPanel>
                </FadeIn>
            </div>
        </>
    );
}

RecurringIndex.layout = {
    breadcrumbs: [{ title: 'Récurrences', href: '/recurring' }],
};
