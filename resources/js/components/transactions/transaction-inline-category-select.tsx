import { router } from '@inertiajs/react';

import { CategorySelect } from '@/components/categories/category-select';
import type { CategorySelectOption } from '@/types/category.types';

type Props = {
    transactionId: number;
    value: number | null;
    options: CategorySelectOption[];
    noneLabel: string;
};

export function TransactionInlineCategorySelect({
    transactionId,
    value,
    options,
    noneLabel,
}: Props) {
    return (
        <CategorySelect
            value={value}
            onChange={(categoryId) => {
                router.patch(
                    `/transactions/${transactionId}/category`,
                    { category_id: categoryId },
                    { preserveScroll: true },
                );
            }}
            options={options}
            noneLabel={noneLabel}
        />
    );
}
