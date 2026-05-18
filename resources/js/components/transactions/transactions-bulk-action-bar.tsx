import { router } from '@inertiajs/react';
import { Tags, Trash2, X } from 'lucide-react';
import { useState } from 'react';

import { CategorySelect } from '@/components/categories/category-select';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { CategorySelectOption } from '@/types/category.types';

type TransactionsBulkActionBarProps = {
    selectedIds: number[];
    uncategorizedSelectedCount: number;
    categoryOptions: CategorySelectOption[];
    onClearSelection: () => void;
};

export function TransactionsBulkActionBar({
    selectedIds,
    uncategorizedSelectedCount,
    categoryOptions,
    onClearSelection,
}: TransactionsBulkActionBarProps) {
    const { t } = useTranslation();
    const [categoryId, setCategoryId] = useState<number | null>(null);
    const [processing, setProcessing] = useState(false);

    if (selectedIds.length === 0) {
        return null;
    }

    const postBulk = (
        url: string,
        data: Record<string, unknown>,
        method: 'post' | 'delete' = 'post',
    ) => {
        setProcessing(true);
        const options = {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                onClearSelection();
            },
        };

        if (method === 'delete') {
            router.delete(url, { ...options, data });
        } else {
            router.post(url, data, options);
        }
    };

    const applyCategory = () => {
        postBulk('/transactions/bulk/category', {
            transaction_ids: selectedIds,
            category_id: categoryId,
        });
    };

    const applyRules = () => {
        postBulk('/transactions/bulk/apply-rules', {
            transaction_ids: selectedIds,
        });
    };

    const deleteSelected = () => {
        if (
            !window.confirm(
                t('transactions.bulk.delete_confirm', {
                    count: selectedIds.length,
                }),
            )
        ) {
            return;
        }

        postBulk(
            '/transactions/bulk',
            { transaction_ids: selectedIds },
            'delete',
        );
    };

    return (
        <div
            className={cn(
                'border-border/60 bg-background/90 fixed inset-x-0 bottom-0 z-50 border-t backdrop-blur-md',
                'pb-[env(safe-area-inset-bottom)]',
            )}
            role="region"
            aria-label={t('transactions.bulk.selected', { count: selectedIds.length })}
        >
            <div className="mx-auto flex max-w-[1600px] flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-3">
                    <p className="text-sm font-medium tabular-nums">
                        {t('transactions.bulk.selected', {
                            count: selectedIds.length,
                        })}
                    </p>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="h-8 gap-1 px-2"
                        disabled={processing}
                        onClick={onClearSelection}
                    >
                        <X className="size-4" />
                        {t('transactions.bulk.clear_selection')}
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <div className="flex min-w-0 flex-1 items-center gap-2 sm:flex-none sm:min-w-[14rem]">
                        <span className="text-muted-foreground hidden text-xs sm:inline">
                            {t('transactions.bulk.assign_category')}
                        </span>
                        <CategorySelect
                            value={categoryId}
                            onChange={setCategoryId}
                            options={categoryOptions}
                            noneLabel={t('transactions.category_none')}
                        />
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            disabled={processing}
                            onClick={applyCategory}
                        >
                            {t('transactions.bulk.apply_category')}
                        </Button>
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={processing || uncategorizedSelectedCount === 0}
                        onClick={applyRules}
                    >
                        <Tags className="mr-2 size-4" />
                        {t('transactions.bulk.apply_rules')}
                    </Button>

                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        disabled={processing}
                        onClick={deleteSelected}
                    >
                        <Trash2 className="mr-2 size-4" />
                        {t('transactions.bulk.delete')}
                    </Button>
                </div>
            </div>
        </div>
    );
}
