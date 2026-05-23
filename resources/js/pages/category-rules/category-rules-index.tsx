import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

import { CategoryRuleEditDialog } from '@/components/category-rules/category-rule-edit-dialog';
import {
    CategoryRuleFlowSelect,
    categoryRuleFlowLabel,
} from '@/components/category-rules/category-rule-flow-select';
import { CategoryBadge } from '@/components/categories/category-badge';
import { CategorySelect } from '@/components/categories/category-select';
import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type {
    CategoryRuleFlow,
    CategoryRuleRecord,
    CategoryRulesIndexPageProps,
} from '@/types/category-rule.types';

type TestMatchResult = {
    category: { id: number; name: string; color: string | null } | null;
};

function ruleMatchesSearch(rule: CategoryRuleRecord, query: string): boolean {
    const haystack = [
        rule.pattern,
        rule.category_name ?? '',
    ]
        .join(' ')
        .toLowerCase();

    return haystack.includes(query);
}

export default function CategoryRulesIndex({
    rules,
    categoryOptions,
}: CategoryRulesIndexPageProps) {
    const { t } = useTranslation();
    const [search, setSearch] = useState('');
    const [testLabel, setTestLabel] = useState('');
    const [testAmount, setTestAmount] = useState('');
    const [testResult, setTestResult] = useState<TestMatchResult | null>(null);
    const [editingRule, setEditingRule] = useState<CategoryRuleRecord | null>(
        null,
    );

    const form = useForm({
        pattern: '',
        category_id: null as number | null,
        flow: null as CategoryRuleFlow,
        priority: 0,
    });

    const searchQuery = search.trim().toLowerCase();

    const filteredRules = useMemo(() => {
        if (searchQuery === '') {
            return rules;
        }

        return rules.filter((rule) => ruleMatchesSearch(rule, searchQuery));
    }, [rules, searchQuery]);

    const runTest = useCallback(async () => {
        const response = await fetch('/category-rules/test-match', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
            },
            body: JSON.stringify({
                label: testLabel,
                amount: testAmount.trim() === '' ? null : Number.parseFloat(testAmount),
            }),
        });

        if (response.ok) {
            setTestResult((await response.json()) as TestMatchResult);
        }
    }, [testAmount, testLabel]);

    const submitRule = () => {
        if (form.data.category_id === null) {
            return;
        }

        form.transform((data) => ({
            pattern: data.pattern,
            category_id: data.category_id,
            flow: data.flow,
            priority: data.priority,
            is_active: true,
        }));

        form.post('/category-rules', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                form.setData('category_id', null);
                form.setData('priority', 0);
                form.setData('flow', null);
            },
        });
    };

    return (
        <>
            <Head title={t('category_rules.title')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {t('category_rules.title')}
                    </h1>
                    <p className="text-muted-foreground mt-1 max-w-3xl text-sm">
                        {t('category_rules.description')}
                    </p>
                    <p className="text-muted-foreground mt-1 max-w-3xl text-xs">
                        {t('category_rules.specificity_hint')}
                    </p>
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(280px,340px)_1fr]">
                    <div className="flex flex-col gap-6">
                        <FadeIn>
                            <GlassPanel className="space-y-4 p-6">
                                <h2 className="text-sm font-medium">
                                    {t('category_rules.add_rule')}
                                </h2>
                                <div className="grid gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="rule-pattern">
                                            {t('category_rules.pattern')}
                                        </Label>
                                        <Input
                                            id="rule-pattern"
                                            value={form.data.pattern}
                                            onChange={(event) =>
                                                form.setData(
                                                    'pattern',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder={t(
                                                'category_rules.pattern_placeholder',
                                            )}
                                        />
                                        <InputError message={form.errors.pattern} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>
                                            {t('category_rules.target_category')}
                                        </Label>
                                        <CategorySelect
                                            value={form.data.category_id}
                                            onChange={(id) =>
                                                form.setData('category_id', id)
                                            }
                                            options={categoryOptions}
                                            noneLabel={t(
                                                'category_rules.choose_category',
                                            )}
                                        />
                                        <InputError
                                            message={form.errors.category_id}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="rule-flow">
                                            {t('category_rules.flow')}
                                        </Label>
                                        <CategoryRuleFlowSelect
                                            id="rule-flow"
                                            value={form.data.flow}
                                            onChange={(flow) =>
                                                form.setData('flow', flow)
                                            }
                                        />
                                        <InputError message={form.errors.flow} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="rule-priority">
                                            {t('category_rules.priority')}
                                        </Label>
                                        <Input
                                            id="rule-priority"
                                            type="number"
                                            min={0}
                                            value={String(form.data.priority)}
                                            onChange={(event) =>
                                                form.setData(
                                                    'priority',
                                                    Number.parseInt(
                                                        event.target.value,
                                                        10,
                                                    ) || 0,
                                                )
                                            }
                                        />
                                    </div>
                                </div>
                                <Button
                                    type="button"
                                    disabled={
                                        form.processing ||
                                        form.data.category_id === null
                                    }
                                    onClick={submitRule}
                                >
                                    <Plus className="mr-2 size-4" />
                                    {t('category_rules.create_rule')}
                                </Button>
                            </GlassPanel>
                        </FadeIn>

                        <FadeIn>
                            <GlassPanel className="space-y-4 p-6">
                                <h2 className="text-sm font-medium">
                                    {t('category_rules.live_test')}
                                </h2>
                                <div className="flex flex-col gap-2">
                                    <Input
                                        value={testLabel}
                                        onChange={(event) =>
                                            setTestLabel(event.target.value)
                                        }
                                        placeholder={t(
                                            'category_rules.test_placeholder',
                                        )}
                                    />
                                    <Input
                                        type="number"
                                        step="0.01"
                                        value={testAmount}
                                        onChange={(event) =>
                                            setTestAmount(event.target.value)
                                        }
                                        placeholder={t('transactions.amount')}
                                    />
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={runTest}
                                    >
                                        {t('category_rules.test_button')}
                                    </Button>
                                </div>
                                {testResult?.category ? (
                                    <div className="flex flex-wrap items-center gap-2 text-sm">
                                        <span>{t('category_rules.test_match')}</span>
                                        <CategoryBadge
                                            name={testResult.category.name}
                                            color={testResult.category.color}
                                        />
                                    </div>
                                ) : testResult !== null ? (
                                    <p className="text-muted-foreground text-sm">
                                        {t('category_rules.test_no_match')}
                                    </p>
                                ) : null}
                            </GlassPanel>
                        </FadeIn>
                    </div>

                    <FadeIn>
                        <GlassPanel className="flex min-h-[min(70vh,720px)] flex-col p-0">
                            <div className="border-border/60 flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div className="relative min-w-0 flex-1 sm:max-w-md">
                                    <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                    <Input
                                        value={search}
                                        onChange={(event) =>
                                            setSearch(event.target.value)
                                        }
                                        placeholder={t(
                                            'category_rules.search_placeholder',
                                        )}
                                        className="pl-9"
                                        aria-label={t(
                                            'category_rules.search_placeholder',
                                        )}
                                    />
                                </div>
                                <p className="text-muted-foreground shrink-0 text-sm tabular-nums">
                                    {searchQuery !== ''
                                        ? t('category_rules.rules_count', {
                                              count: filteredRules.length,
                                          })
                                        : t('category_rules.rules_count', {
                                              count: rules.length,
                                          })}
                                </p>
                            </div>

                            {rules.length === 0 ? (
                                <p className="text-muted-foreground p-6 text-sm">
                                    {t('category_rules.empty')}
                                </p>
                            ) : filteredRules.length === 0 ? (
                                <p className="text-muted-foreground p-6 text-sm">
                                    {t('category_rules.search_no_results')}
                                </p>
                            ) : (
                                <div className="min-h-0 flex-1 overflow-auto">
                                    <table className="w-full min-w-[640px] text-sm">
                                        <thead className="text-muted-foreground bg-muted/20 sticky top-0 z-10 border-b text-left text-xs uppercase backdrop-blur-sm">
                                            <tr>
                                                <th className="px-4 py-3">
                                                    {t('category_rules.pattern')}
                                                </th>
                                                <th className="px-4 py-3">
                                                    {t(
                                                        'category_rules.target_category',
                                                    )}
                                                </th>
                                                <th className="px-4 py-3">
                                                    {t('category_rules.flow')}
                                                </th>
                                                <th className="px-4 py-3">
                                                    {t('category_rules.priority')}
                                                </th>
                                                <th className="px-4 py-3">
                                                    {t('category_rules.active')}
                                                </th>
                                                <th className="px-4 py-3" />
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {filteredRules.map((rule) => (
                                                <tr
                                                    key={rule.id}
                                                    className={cn(
                                                        'border-border/40 border-b last:border-0',
                                                        searchQuery !== '' &&
                                                            'bg-primary/5',
                                                    )}
                                                >
                                                    <td className="px-4 py-3 font-mono text-xs">
                                                        {rule.pattern}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {rule.category_name ? (
                                                            <CategoryBadge
                                                                name={
                                                                    rule.category_name
                                                                }
                                                                color={
                                                                    rule.category_color
                                                                }
                                                            />
                                                        ) : (
                                                            <span className="text-muted-foreground text-xs">
                                                                —
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="text-muted-foreground px-4 py-3 text-xs">
                                                        {categoryRuleFlowLabel(
                                                            rule.flow,
                                                            t,
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 tabular-nums">
                                                        {rule.priority}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Checkbox
                                                            checked={rule.is_active}
                                                            onCheckedChange={() =>
                                                                router.put(
                                                                    `/category-rules/${rule.id}`,
                                                                    {
                                                                        is_active:
                                                                            !rule.is_active,
                                                                    },
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                            aria-label={t(
                                                                'category_rules.active',
                                                            )}
                                                        />
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        <div className="flex justify-end gap-1">
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() =>
                                                                    setEditingRule(
                                                                        rule,
                                                                    )
                                                                }
                                                            >
                                                                <Pencil className="size-4" />
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() =>
                                                                    router.delete(
                                                                        `/category-rules/${rule.id}`,
                                                                        {
                                                                            preserveScroll: true,
                                                                        },
                                                                    )
                                                                }
                                                            >
                                                                <Trash2 className="size-4" />
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </GlassPanel>
                    </FadeIn>
                </div>
            </div>

            <CategoryRuleEditDialog
                rule={editingRule}
                categoryOptions={categoryOptions}
                open={editingRule !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditingRule(null);
                    }
                }}
            />
        </>
    );
}

CategoryRulesIndex.layout = {
    breadcrumbs: [
        { titleKey: 'nav.configuration', href: '/categories' },
        { titleKey: 'nav.category_rules', href: '/category-rules' },
    ],
};
