import { Link, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronLeft,
    ChevronRight,
    ListFilter,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

import { CategoryFilterSelect } from '@/components/categories/category-select';
import { EntityLogo } from '@/components/entity-logo';
import { RecurringBadge } from '@/components/recurring/recurring-badge';
import { ApplyCategoryRulesButton } from '@/components/transactions/apply-category-rules-button';
import { TransactionInlineCategorySelect } from '@/components/transactions/transaction-inline-category-select';
import { TransactionTypeBadge } from '@/components/transactions/transaction-type-badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
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
import { buildTransactionsIndexQuery } from '@/lib/transactions-index-query';
import { transactionsIndexEditUrl } from '@/lib/transaction-edit-url';
import { cn } from '@/lib/utils';
import type { TransactionSortColumn } from '@/types/account.types';
import type { CategorySelectOption } from '@/types/category.types';
import type {
    PaginatedTransactions,
    TransactionAccountOption,
    TransactionListFilters,
    TransactionTypeOption,
} from '@/types/transaction.types';

const ALL_VALUE = '__all__';

type SortIconProps = {
    column: TransactionSortColumn;
    sort: TransactionSortColumn;
    order: TransactionListFilters['order'];
};

function SortIcon({ column, sort, order }: SortIconProps) {
    if (sort !== column) {
        return <ArrowUpDown className="ml-1 size-3.5 opacity-50" />;
    }

    return order === 'asc' ? (
        <ArrowUp className="ml-1 size-3.5" />
    ) : (
        <ArrowDown className="ml-1 size-3.5" />
    );
}

type TransactionsIndexPanelProps = {
    transactions: PaginatedTransactions;
    filters: TransactionListFilters;
    categoryOptions: CategorySelectOption[];
    accountOptions: TransactionAccountOption[];
    typeOptions: TransactionTypeOption[];
    perPageOptions: number[];
    uncategorizedCount: number;
};

export function TransactionsIndexPanel({
    transactions,
    filters,
    categoryOptions,
    accountOptions,
    typeOptions,
    perPageOptions,
    uncategorizedCount,
}: TransactionsIndexPanelProps) {
    const { t } = useTranslation();
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        setSearch(filters.search);
    }, [filters.search]);

    const navigate = useCallback(
        (next: Partial<TransactionListFilters>) => {
            const merged: TransactionListFilters = { ...filters, ...next };
            router.get('/transactions', buildTransactionsIndexQuery(merged), {
                preserveState: true,
                preserveScroll: true,
            });
        },
        [filters],
    );

    useEffect(() => {
        const trimmed = search.trim();

        if (trimmed === filters.search.trim()) {
            return;
        }

        const timer = window.setTimeout(() => {
            navigate({ search: trimmed, page: 1 });
        }, 400);

        return () => window.clearTimeout(timer);
    }, [navigate, search, filters.search]);

    const handleSort = (column: TransactionSortColumn) => {
        const nextOrder =
            filters.sort === column && filters.order === 'desc' ? 'asc' : 'desc';

        navigate({ sort: column, order: nextOrder, page: 1 });
    };

    const hasActiveFilters =
        filters.search.trim() !== '' ||
        filters.date_from !== null ||
        filters.date_to !== null ||
        filters.type !== null ||
        filters.flow !== null ||
        filters.category_id !== null ||
        filters.account_id !== null ||
        (filters.amount_min !== null && filters.amount_min !== '') ||
        (filters.amount_max !== null && filters.amount_max !== '');

    const { meta } = transactions;

    const resetFilters = () => {
        setSearch('');
        navigate({
            search: '',
            date_from: null,
            date_to: null,
            type: null,
            flow: null,
            category_id: null,
            account_id: null,
            amount_min: null,
            amount_max: null,
            page: 1,
        });
    };

    return (
        <div className="flex flex-col gap-4">
            <Collapsible
                open={filtersOpen}
                onOpenChange={setFiltersOpen}
                className="flex flex-col gap-3"
            >
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <CollapsibleTrigger asChild>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="gap-2"
                            aria-expanded={filtersOpen}
                        >
                            <ListFilter className="size-4" />
                            {filtersOpen
                                ? t('accounts.transactions_list.hide_filters')
                                : t('accounts.transactions_list.show_filters')}
                            {hasActiveFilters ? (
                                <span
                                    className="bg-primary size-2 shrink-0 rounded-full"
                                    aria-hidden
                                />
                            ) : null}
                        </Button>
                    </CollapsibleTrigger>
                    <div className="flex flex-wrap items-center gap-2">
                        {hasActiveFilters ? (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={resetFilters}
                            >
                                {t('accounts.transactions_list.reset_filters')}
                            </Button>
                        ) : null}
                        <Label htmlFor="tx-index-per-page" className="sr-only">
                            {t('accounts.transactions_list.per_page')}
                        </Label>
                        <Select
                            value={String(filters.per_page)}
                            onValueChange={(value) =>
                                navigate({
                                    per_page: Number.parseInt(value, 10),
                                    page: 1,
                                })
                            }
                        >
                            <SelectTrigger id="tx-index-per-page" className="w-[130px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {perPageOptions.map((option) => (
                                    <SelectItem key={option} value={String(option)}>
                                        {t('accounts.transactions_list.per_page_option', {
                                            count: option,
                                        })}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <CollapsibleContent className="flex flex-col gap-3">
                    <div className="space-y-1.5">
                        <Label htmlFor="tx-index-search">{t('transactions.label')}</Label>
                        <Input
                            id="tx-index-search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t(
                                'accounts.transactions_list.search_placeholder',
                            )}
                        />
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="space-y-1.5 min-w-0">
                            <Label htmlFor="tx-index-date-from">
                                {t('accounts.transactions_list.date_from')}
                            </Label>
                            <DatePicker
                                id="tx-index-date-from"
                                value={filters.date_from}
                                clearable
                                onChange={(dateFrom) =>
                                    navigate({
                                        date_from: dateFrom,
                                        page: 1,
                                    })
                                }
                            />
                        </div>
                        <div className="space-y-1.5 min-w-0">
                            <Label htmlFor="tx-index-date-to">
                                {t('accounts.transactions_list.date_to')}
                            </Label>
                            <DatePicker
                                id="tx-index-date-to"
                                value={filters.date_to}
                                clearable
                                onChange={(dateTo) =>
                                    navigate({
                                        date_to: dateTo,
                                        page: 1,
                                    })
                                }
                            />
                        </div>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="tx-index-account">{t('transactions.account')}</Label>
                            <Select
                                value={
                                    filters.account_id !== null
                                        ? String(filters.account_id)
                                        : ALL_VALUE
                                }
                                onValueChange={(value) =>
                                    navigate({
                                        account_id:
                                            value === ALL_VALUE
                                                ? null
                                                : Number.parseInt(value, 10),
                                        page: 1,
                                    })
                                }
                            >
                                <SelectTrigger id="tx-index-account">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_VALUE}>
                                        {t('accounts.transactions_list.filter_all')}
                                    </SelectItem>
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
                        </div>

                        <div className="space-y-1.5">
                            <Label>{t('transactions.type')}</Label>
                            <Select
                                value={filters.type ?? ALL_VALUE}
                                onValueChange={(value) =>
                                    navigate({
                                        type:
                                            value === ALL_VALUE
                                                ? null
                                                : (value as TransactionListFilters['type']),
                                        page: 1,
                                    })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_VALUE}>
                                        {t('accounts.transactions_list.filter_all')}
                                    </SelectItem>
                                    {typeOptions.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label>{t('transactions.category')}</Label>
                            <CategoryFilterSelect
                                value={filters.category_id}
                                onChange={(category_id) =>
                                    navigate({ category_id, page: 1 })
                                }
                                options={categoryOptions}
                                allLabel={t('accounts.transactions_list.filter_all')}
                                uncategorizedLabel={t('transactions.category_none')}
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label>{t('accounts.transactions_list.flow')}</Label>
                            <Select
                                value={filters.flow ?? ALL_VALUE}
                                onValueChange={(value) =>
                                    navigate({
                                        flow:
                                            value === ALL_VALUE
                                                ? null
                                                : (value as TransactionListFilters['flow']),
                                        page: 1,
                                    })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_VALUE}>
                                        {t('accounts.transactions_list.filter_all')}
                                    </SelectItem>
                                    <SelectItem value="credit">
                                        {t('accounts.transactions_list.flow_credit')}
                                    </SelectItem>
                                    <SelectItem value="debit">
                                        {t('accounts.transactions_list.flow_debit')}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="tx-index-amount-min">
                                {t('transactions.amount_min')}
                            </Label>
                            <Input
                                id="tx-index-amount-min"
                                type="number"
                                step="0.01"
                                value={filters.amount_min ?? ''}
                                onChange={(event) =>
                                    navigate({
                                        amount_min: event.target.value || null,
                                        page: 1,
                                    })
                                }
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="tx-index-amount-max">
                                {t('transactions.amount_max')}
                            </Label>
                            <Input
                                id="tx-index-amount-max"
                                type="number"
                                step="0.01"
                                value={filters.amount_max ?? ''}
                                onChange={(event) =>
                                    navigate({
                                        amount_max: event.target.value || null,
                                        page: 1,
                                    })
                                }
                            />
                        </div>
                    </div>
                </CollapsibleContent>
            </Collapsible>

            <div className="flex flex-wrap items-center gap-2">
                <ApplyCategoryRulesButton
                    uncategorizedCount={uncategorizedCount}
                    accountId={filters.account_id ?? undefined}
                />
            </div>
            {transactions.data.length === 0 ? (
                <p className="text-muted-foreground text-sm">
                    {hasActiveFilters
                        ? t('accounts.transactions_list.no_results')
                        : t('accounts.no_transactions')}
                </p>
            ) : (
                <>
                    <div className="overflow-x-auto rounded-xl border border-white/10">
                        <table className="w-full min-w-[960px] text-sm">
                            <thead>
                                <tr className="text-muted-foreground border-border/60 border-b text-left text-xs uppercase tracking-wide">
                                    <th className="px-4 py-3">
                                        <button
                                            type="button"
                                            className="hover:text-foreground inline-flex items-center font-medium"
                                            onClick={() => handleSort('date')}
                                        >
                                            {t('transactions.date')}
                                            <SortIcon
                                                column="date"
                                                sort={filters.sort}
                                                order={filters.order}
                                            />
                                        </button>
                                    </th>
                                    <th className="px-4 py-3">
                                        <button
                                            type="button"
                                            className="hover:text-foreground inline-flex items-center font-medium"
                                            onClick={() => handleSort('account')}
                                        >
                                            {t('transactions.account')}
                                            <SortIcon
                                                column="account"
                                                sort={filters.sort}
                                                order={filters.order}
                                            />
                                        </button>
                                    </th>
                                    <th className="px-4 py-3">
                                        <button
                                            type="button"
                                            className="hover:text-foreground inline-flex items-center font-medium"
                                            onClick={() => handleSort('label')}
                                        >
                                            {t('transactions.label')}
                                            <SortIcon
                                                column="label"
                                                sort={filters.sort}
                                                order={filters.order}
                                            />
                                        </button>
                                    </th>
                                    <th className="px-4 py-3">
                                        <button
                                            type="button"
                                            className="hover:text-foreground inline-flex items-center font-medium"
                                            onClick={() => handleSort('type')}
                                        >
                                            {t('transactions.type')}
                                            <SortIcon
                                                column="type"
                                                sort={filters.sort}
                                                order={filters.order}
                                            />
                                        </button>
                                    </th>
                                    <th className="w-[15rem] min-w-[15rem] px-4 py-3">
                                        <button
                                            type="button"
                                            className="hover:text-foreground inline-flex items-center font-medium"
                                            onClick={() => handleSort('category')}
                                        >
                                            {t('transactions.category')}
                                            <SortIcon
                                                column="category"
                                                sort={filters.sort}
                                                order={filters.order}
                                            />
                                        </button>
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        <button
                                            type="button"
                                            className={cn(
                                                'hover:text-foreground ml-auto inline-flex items-center font-medium',
                                            )}
                                            onClick={() => handleSort('amount')}
                                        >
                                            {t('transactions.amount')}
                                            <SortIcon
                                                column="amount"
                                                sort={filters.sort}
                                                order={filters.order}
                                            />
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {transactions.data.map((transaction) => (
                                    <tr
                                        key={transaction.id}
                                        className="border-border/40 hover:bg-muted/30 border-b last:border-0"
                                    >
                                        <td className="text-muted-foreground px-4 py-3 tabular-nums">
                                            {transaction.date}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Link
                                                href={`/accounts/${transaction.account_id}`}
                                                className="flex items-center gap-2 hover:underline"
                                            >
                                                <EntityLogo
                                                    name={transaction.account_name ?? ''}
                                                    logoUrl={transaction.account_logo_url}
                                                    className="size-5"
                                                />
                                                {transaction.account_name}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3">
                                            <Link
                                                href={transactionsIndexEditUrl(
                                                    transaction.id,
                                                    filters,
                                                )}
                                                preserveScroll
                                                className="inline-flex items-center font-medium hover:underline"
                                            >
                                                {transaction.label}
                                                {transaction.recurring_display_label ? (
                                                    <RecurringBadge
                                                        label={
                                                            transaction.recurring_display_label
                                                        }
                                                    />
                                                ) : null}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3">
                                            <TransactionTypeBadge type={transaction.type} />
                                        </td>
                                        <td className="w-[15rem] min-w-[15rem] max-w-[15rem] px-4 py-3">
                                            <TransactionInlineCategorySelect
                                                transactionId={transaction.id}
                                                value={transaction.category_id}
                                                options={categoryOptions}
                                                noneLabel={t('transactions.category_none')}
                                            />
                                        </td>
                                        <td className="px-4 py-3 text-right font-mono tabular-nums">
                                            {formatCurrency(transaction.amount, {
                                                precise: true,
                                            })}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-muted-foreground text-sm">
                            {meta.from !== null && meta.to !== null
                                ? t('accounts.transactions_list.pagination_summary', {
                                      from: meta.from,
                                      to: meta.to,
                                      total: meta.total,
                                  })
                                : t('accounts.transactions_list.pagination_empty')}
                        </p>
                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                disabled={meta.current_page <= 1}
                                onClick={() =>
                                    navigate({ page: meta.current_page - 1 })
                                }
                            >
                                <ChevronLeft className="size-4" />
                                <span className="sr-only">
                                    {t('accounts.transactions_list.previous')}
                                </span>
                            </Button>
                            <span className="text-muted-foreground text-sm tabular-nums">
                                {t('accounts.transactions_list.page_of', {
                                    page: meta.current_page,
                                    last: meta.last_page,
                                })}
                            </span>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                disabled={meta.current_page >= meta.last_page}
                                onClick={() =>
                                    navigate({ page: meta.current_page + 1 })
                                }
                            >
                                <ChevronRight className="size-4" />
                                <span className="sr-only">
                                    {t('accounts.transactions_list.next')}
                                </span>
                            </Button>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
