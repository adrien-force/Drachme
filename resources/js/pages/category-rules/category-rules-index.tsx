import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useCallback, useState } from 'react';

import { CategoryRuleEditDialog } from '@/components/category-rules/category-rule-edit-dialog';
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
import type {
    CategoryRuleRecord,
    CategoryRulesIndexPageProps,
} from '@/types/category-rule.types';

type TestMatchResult = {
    category: { id: number; name: string; color: string | null } | null;
};

export default function CategoryRulesIndex({
    rules,
    categoryOptions,
}: CategoryRulesIndexPageProps) {
    const { t } = useTranslation();
    const [testLabel, setTestLabel] = useState('');
    const [testResult, setTestResult] = useState<TestMatchResult | null>(null);
    const [editingRule, setEditingRule] = useState<CategoryRuleRecord | null>(
        null,
    );

    const form = useForm({
        pattern: '',
        category_id: null as number | null,
        priority: 0,
    });

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
            body: JSON.stringify({ label: testLabel }),
        });

        if (response.ok) {
            setTestResult((await response.json()) as TestMatchResult);
        }
    }, [testLabel]);

    const submitRule = () => {
        if (form.data.category_id === null) {
            return;
        }

        form.transform((data) => ({
            pattern: data.pattern,
            category_id: data.category_id,
            priority: data.priority,
            is_active: true,
        }));

        form.post('/category-rules', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                form.setData('category_id', null);
                form.setData('priority', 0);
            },
        });
    };

    return (
        <>
            <Head title={t('category_rules.title')} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {t('category_rules.title')}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {t('category_rules.description')}
                    </p>
                </div>

                <FadeIn>
                    <GlassPanel className="space-y-4 p-6">
                        <h2 className="text-sm font-medium">
                            {t('category_rules.add_rule')}
                        </h2>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="rule-pattern">
                                    {t('category_rules.pattern')}
                                </Label>
                                <Input
                                    id="rule-pattern"
                                    value={form.data.pattern}
                                    onChange={(event) =>
                                        form.setData('pattern', event.target.value)
                                    }
                                    placeholder={t('category_rules.pattern_placeholder')}
                                />
                                <InputError message={form.errors.pattern} />
                            </div>
                            <div className="space-y-2">
                                <Label>{t('category_rules.target_category')}</Label>
                                <CategorySelect
                                    value={form.data.category_id}
                                    onChange={(id) => form.setData('category_id', id)}
                                    options={categoryOptions}
                                    noneLabel={t('category_rules.choose_category')}
                                />
                                <InputError message={form.errors.category_id} />
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
                                            Number.parseInt(event.target.value, 10) || 0,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <Button
                            type="button"
                            disabled={form.processing || form.data.category_id === null}
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
                        <div className="flex flex-col gap-2 sm:flex-row">
                            <Input
                                value={testLabel}
                                onChange={(event) => setTestLabel(event.target.value)}
                                placeholder={t('category_rules.test_placeholder')}
                            />
                            <Button type="button" variant="secondary" onClick={runTest}>
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

                <FadeIn>
                    <GlassPanel className="overflow-x-auto p-0">
                        {rules.length === 0 ? (
                            <p className="text-muted-foreground p-6 text-sm">
                                {t('category_rules.empty')}
                            </p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead className="text-muted-foreground border-border/60 border-b text-left text-xs uppercase">
                                    <tr>
                                        <th className="px-4 py-3">
                                            {t('category_rules.pattern')}
                                        </th>
                                        <th className="px-4 py-3">
                                            {t('category_rules.target_category')}
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
                                    {rules.map((rule) => (
                                        <tr
                                            key={rule.id}
                                            className="border-border/40 border-b last:border-0"
                                        >
                                            <td className="px-4 py-3 font-mono text-xs">
                                                {rule.pattern}
                                            </td>
                                            <td className="px-4 py-3">
                                                {rule.category_name ? (
                                                    <CategoryBadge
                                                        name={rule.category_name}
                                                        color={rule.category_color}
                                                    />
                                                ) : (
                                                    <span className="text-muted-foreground text-xs">
                                                        —
                                                    </span>
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
                                                                is_active: !rule.is_active,
                                                            },
                                                            { preserveScroll: true },
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
                                                            setEditingRule(rule)
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
                                                                { preserveScroll: true },
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
                        )}
                    </GlassPanel>
                </FadeIn>
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
        { title: 'Paramétrage', href: '/categories' },
        { title: 'Règles', href: '/category-rules' },
    ],
};
