import { router } from '@inertiajs/react';
import { AnimatePresence, motion } from 'motion/react';
import { ChevronLeft, ChevronRight, RotateCcw, SkipForward, Tags } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

import { CardSettlementBadge } from '@/components/transactions/card-settlement-badge';
import { TransactionTypeBadge } from '@/components/transactions/transaction-type-badge';
import { EntityLogo } from '@/components/entity-logo';
import { FadeIn } from '@/components/motion/fade-in';
import {
    CategoryRuleFlowSelect,
    flowFromAmount,
} from '@/components/category-rules/category-rule-flow-select';
import { CategorySelect } from '@/components/categories/category-select';
import { GlassPanel } from '@/components/glass-panel';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { RECURRING_FREQUENCIES, recurringFrequencyLabel } from '@/lib/recurring-frequency';
import { cn } from '@/lib/utils';
import type { CategoryRuleFlow } from '@/types/category-rule.types';
import type { RecurringFrequency } from '@/types/recurring.types';
import type { TransactionsTriagePageProps } from '@/types/transaction-triage.types';

const NO_RECURRING = '__none__';

type TransactionTriagePanelProps = TransactionsTriagePageProps;

export function TransactionTriagePanel({
    transaction,
    remaining,
    totalUncategorized,
    skipIds,
    categoryOptions,
}: TransactionTriagePanelProps) {
    const { t } = useTranslation();
    const [processing, setProcessing] = useState(false);

    const [categoryId, setCategoryId] = useState<string>(
        transaction?.suggested_category_id != null
            ? String(transaction.suggested_category_id)
            : '',
    );
    const [createRule, setCreateRule] = useState(false);
    const [ruleFlow, setRuleFlow] = useState<CategoryRuleFlow>(null);
    const [selectedTokens, setSelectedTokens] = useState<string[]>(
        transaction?.label_tokens ?? [],
    );
    const [recurringFrequency, setRecurringFrequency] = useState<string>(NO_RECURRING);

    useEffect(() => {
        setCategoryId(
            transaction?.suggested_category_id != null
                ? String(transaction.suggested_category_id)
                : '',
        );
        setSelectedTokens(transaction?.label_tokens ?? []);
        setCreateRule(false);
        setRuleFlow(
            transaction != null ? flowFromAmount(transaction.amount) : null,
        );
        setRecurringFrequency(NO_RECURRING);
    }, [
        transaction?.amount,
        transaction?.id,
        transaction?.label_tokens,
        transaction?.suggested_category_id,
    ]);

    const progressLabel = useMemo(() => {
        if (totalUncategorized === 0) {
            return t('transactions.triage.all_done');
        }

        const currentIndex = totalUncategorized - remaining;

        return t('transactions.triage.progress', {
            current: currentIndex,
            total: totalUncategorized,
        });
    }, [remaining, totalUncategorized, t]);

    const toggleToken = (token: string) => {
        setSelectedTokens((current) =>
            current.includes(token)
                ? current.filter((value) => value !== token)
                : [...current, token],
        );
    };

    const submit = (action: 'categorize' | 'skip') => {
        if (transaction == null || processing) {
            return;
        }

        setProcessing(true);

        const payload =
            action === 'skip'
                ? {
                      action: 'skip' as const,
                      skip_ids: [...skipIds, transaction.id],
                  }
                : {
                      action: 'categorize' as const,
                      category_id: Number.parseInt(categoryId, 10),
                      create_rule: createRule,
                      selected_tokens: createRule ? selectedTokens : [],
                      flow: createRule ? ruleFlow : null,
                      recurring_frequency:
                          recurringFrequency !== NO_RECURRING
                              ? (recurringFrequency as RecurringFrequency)
                              : null,
                      skip_ids: skipIds,
                  };

        router.post(`/transactions/${transaction.id}/triage`, payload, {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
        });
    };

    const resetSkipped = () => {
        router.get('/transactions/triage', {}, { preserveScroll: true });
    };

    if (transaction == null) {
        return (
                <GlassPanel className="mx-auto max-w-lg space-y-6 p-8 text-center">
                    <p className="text-lg font-semibold">{t('transactions.triage.all_done')}</p>
                    <p className="text-muted-foreground text-sm">
                        {t('transactions.triage.all_done_hint')}
                    </p>
                    <div className="flex flex-wrap justify-center gap-2">
                        {skipIds.length > 0 ? (
                            <Button type="button" variant="outline" onClick={resetSkipped}>
                                <RotateCcw className="mr-2 size-4" />
                                {t('transactions.triage.reset_skipped', {
                                    count: skipIds.length,
                                })}
                            </Button>
                        ) : null}
                        <Button type="button" variant="outline" asChild>
                            <a href="/transactions">{t('transactions.triage.back_to_list')}</a>
                        </Button>
                    </div>
                </GlassPanel>
        );
    }

    return (
        <motion.div className="mx-auto flex w-full max-w-xl flex-col gap-4">
            <FadeIn>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-muted-foreground text-sm">{progressLabel}</p>
                    <div className="flex flex-wrap gap-2">
                        {skipIds.length > 0 ? (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                disabled={processing}
                                onClick={resetSkipped}
                            >
                                <RotateCcw className="mr-2 size-4" />
                                {t('transactions.triage.reset_skipped', {
                                    count: skipIds.length,
                                })}
                            </Button>
                        ) : null}
                    </div>
                </div>
            </FadeIn>

            <AnimatePresence mode="wait">
                <motion.div
                    key={transaction.id}
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -12 }}
                    transition={{ duration: 0.2 }}
                >
                    <GlassPanel className="space-y-6 p-6 md:p-8">
                        <div className="space-y-3 text-center">
                            <p className="text-muted-foreground text-sm tabular-nums">
                                {transaction.date}
                            </p>
                            <div className="flex flex-wrap items-center justify-center gap-2">
                                <EntityLogo
                                    name={transaction.account_name ?? ''}
                                    logoUrl={transaction.account_logo_url}
                                    className="size-8"
                                />
                                <span className="text-muted-foreground text-sm">
                                    {transaction.account_name}
                                </span>
                                <TransactionTypeBadge type={transaction.type} />
                                {transaction.is_card_settlement ? (
                                    <CardSettlementBadge />
                                ) : null}
                            </div>
                            <p className="text-xl font-semibold leading-snug">
                                {transaction.label}
                            </p>
                            <p
                                className={cn(
                                    'text-3xl font-bold tabular-nums',
                                    transaction.amount < 0
                                        ? 'text-destructive'
                                        : 'text-emerald-500',
                                )}
                            >
                                {formatCurrency(transaction.amount, { precise: true })}
                            </p>
                            {transaction.suggested_category_id !== null ? (
                                <p className="text-muted-foreground text-xs">
                                    {t('transactions.triage.suggested', {
                                        name: transaction.suggested_category_name ?? '',
                                    })}
                                </p>
                            ) : null}
                        </div>

                        <div className="space-y-2">
                            <Label>{t('transactions.category')}</Label>
                            <CategorySelect
                                value={categoryId}
                                onChange={setCategoryId}
                                options={categoryOptions}
                                placeholder={t('transactions.triage.pick_category')}
                            />
                        </div>

                        {transaction.label_tokens.length > 0 ? (
                            <div className="space-y-3 rounded-lg border border-border/60 p-4">
                                <div className="flex items-start gap-2">
                                    <Checkbox
                                        id="create_rule"
                                        checked={createRule}
                                        onCheckedChange={(checked) =>
                                            setCreateRule(checked === true)
                                        }
                                    />
                                    <div className="space-y-1">
                                        <Label
                                            htmlFor="create_rule"
                                            className="cursor-pointer font-medium"
                                        >
                                            {t('transactions.triage.create_rule')}
                                        </Label>
                                        <p className="text-muted-foreground text-xs">
                                            {t('transactions.triage.create_rule_hint')}
                                        </p>
                                    </div>
                                </div>
                                {createRule ? (
                                    <div className="space-y-3">
                                        <div className="space-y-2">
                                            <Label htmlFor="triage-rule-flow">
                                                {t('category_rules.flow')}
                                            </Label>
                                            <CategoryRuleFlowSelect
                                                id="triage-rule-flow"
                                                value={ruleFlow}
                                                onChange={setRuleFlow}
                                            />
                                            <p className="text-muted-foreground text-xs">
                                                {t('category_rules.flow_hint')}
                                            </p>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            {transaction.label_tokens.map((token) => {
                                            const active = selectedTokens.includes(token);

                                            return (
                                                <button
                                                    key={token}
                                                    type="button"
                                                    onClick={() => toggleToken(token)}
                                                    className={cn(
                                                        'rounded-full border px-3 py-1 text-sm transition-colors',
                                                        active
                                                            ? 'border-primary bg-primary/15 text-primary'
                                                            : 'border-border/60 text-muted-foreground hover:bg-muted/40',
                                                    )}
                                                >
                                                    {token}
                                                </button>
                                            );
                                            })}
                                        </div>
                                    </div>
                                ) : null}
                            </div>
                        ) : null}

                        <div className="space-y-2">
                            <Label>{t('transactions.triage.recurring_label')}</Label>
                            <Select
                                value={recurringFrequency}
                                onValueChange={setRecurringFrequency}
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder={t(
                                            'transactions.triage.recurring_optional',
                                        )}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NO_RECURRING}>
                                        {t('transactions.triage.recurring_none')}
                                    </SelectItem>
                                    {RECURRING_FREQUENCIES.map((frequency) => (
                                        <SelectItem key={frequency} value={frequency}>
                                            {recurringFrequencyLabel(frequency, t)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="grid grid-cols-2 gap-3 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="lg"
                                disabled={processing}
                                onClick={() => submit('skip')}
                                className="h-12"
                            >
                                <SkipForward className="mr-2 size-4" />
                                {t('transactions.triage.skip')}
                            </Button>
                            <Button
                                type="button"
                                size="lg"
                                disabled={processing || categoryId === ''}
                                onClick={() => submit('categorize')}
                                className="h-12"
                            >
                                <Tags className="mr-2 size-4" />
                                {t('transactions.triage.categorize')}
                            </Button>
                        </div>

                        <p className="text-muted-foreground text-center text-xs">
                            <ChevronLeft className="mr-1 inline size-3" />
                            {t('transactions.triage.skip')}
                            <span className="mx-2">·</span>
                            {t('transactions.triage.categorize')}
                            <ChevronRight className="ml-1 inline size-3" />
                        </p>
                    </GlassPanel>
                </motion.div>
            </AnimatePresence>
        </motion.div>
    );
}
