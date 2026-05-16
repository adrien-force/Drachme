import { Link, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronLeft,
    ChevronRight,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

import { TransactionTypeBadge } from '@/components/transactions/transaction-type-badge';
import { Button } from '@/components/ui/button';
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
import { buildAccountShowQuery } from '@/lib/account-show-query';
import { formatCurrency } from '@/lib/format-currency';
import { cn } from '@/lib/utils';
import type {
    AccountBalanceHistory,
    AccountTransactionFilters,
    PaginatedTransactions,
    SortOrder,
    TransactionSortColumn,
} from '@/types/account.types';
import type { TransactionTypeOption } from '@/types/transaction.types';

const ALL_VALUE = '__all__';

type AccountTransactionsPanelProps = {
    accountId: number;
    transactions: PaginatedTransactions;
    transactionFilters: AccountTransactionFilters;
    transactionTypeOptions: TransactionTypeOption[];
    perPageOptions: number[];
    balanceHistory: AccountBalanceHistory;
};

function SortIcon({
    column,
    sort,
    order,
}: {
    column: TransactionSortColumn;
    sort: TransactionSortColumn;
    order: SortOrder;
}) {
    if (sort !== column) {
        return <ArrowUpDown className="ml-1 size-3.5 opacity-50" />;
    }

    return order === 'asc' ? (
        <ArrowUp className="ml-1 size-3.5" />
    ) : (
        <ArrowDown className="ml-1 size-3.5" />
    );
}

export function AccountTransactionsPanel({
    accountId,
    transactions,
    transactionFilters,
    transactionTypeOptions,
    perPageOptions,
    balanceHistory,
}: AccountTransactionsPanelProps) {
    const { t } = useTranslation();
    const [search, setSearch] = useState(transactionFilters.search);

    useEffect(() => {
        setSearch(transactionFilters.search);
    }, [transactionFilters.search]);

    const navigate = useCallback(
        (next: Partial<AccountTransactionFilters>) => {
            const merged: AccountTransactionFilters = {
                ...transactionFilters,
                ...next,
            };

            router.get(
                `/accounts/${accountId}`,
                buildAccountShowQuery(balanceHistory, merged),
                { preserveState: true, preserveScroll: true },
            );
        },
        [accountId, balanceHistory, transactionFilters],
    );

    useEffect(() => {
        const trimmed = search.trim();

        if (trimmed === transactionFilters.search.trim()) {
            return;
        }

        const timer = window.setTimeout(() => {
            navigate({ search: trimmed, page: 1 });
        }, 400);

        return () => window.clearTimeout(timer);
    }, [navigate, search, transactionFilters.search]);

    const handleSort = (column: TransactionSortColumn) => {
        const nextOrder: SortOrder =
            transactionFilters.sort === column &&
            transactionFilters.order === 'desc'
                ? 'asc'
                : 'desc';

        navigate({ sort: column, order: nextOrder, page: 1 });
    };

    const hasActiveFilters =
        transactionFilters.search.trim() !== '' ||
        transactionFilters.date_from !== null ||
        transactionFilters.date_to !== null ||
        transactionFilters.type !== null ||
        transactionFilters.flow !== null;

    const { meta } = transactions;

    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <h2 className="text-lg font-semibold">
                    {t('accounts.transactions_list.title')}
                </h2>
                <div className="flex flex-wrap items-center gap-2">
                    <Label htmlFor="tx-per-page" className="sr-only">
                        {t('accounts.transactions_list.per_page')}
                    </Label>
                    <Select
                        value={String(transactionFilters.per_page)}
                        onValueChange={(value) =>
                            navigate({
                                per_page: Number.parseInt(value, 10),
                                page: 1,
                            })
                        }
                    >
                        <SelectTrigger id="tx-per-page" className="w-[130px]">
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

            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <div className="space-y-1.5 xl:col-span-2">
                    <Label htmlFor="tx-search">{t('transactions.label')}</Label>
                    <Input
                        id="tx-search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder={t('accounts.transactions_list.search_placeholder')}
                    />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="tx-date-from">
                        {t('accounts.transactions_list.date_from')}
                    </Label>
                    <Input
                        id="tx-date-from"
                        type="date"
                        value={transactionFilters.date_from ?? ''}
                        onChange={(event) =>
                            navigate({
                                date_from: event.target.value || null,
                                page: 1,
                            })
                        }
                    />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="tx-date-to">
                        {t('accounts.transactions_list.date_to')}
                    </Label>
                    <Input
                        id="tx-date-to"
                        type="date"
                        value={transactionFilters.date_to ?? ''}
                        onChange={(event) =>
                            navigate({
                                date_to: event.target.value || null,
                                page: 1,
                            })
                        }
                    />
                </div>

                <div className="space-y-1.5">
                    <Label>{t('transactions.type')}</Label>
                    <Select
                        value={transactionFilters.type ?? ALL_VALUE}
                        onValueChange={(value) =>
                            navigate({
                                type:
                                    value === ALL_VALUE
                                        ? null
                                        : (value as AccountTransactionFilters['type']),
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
                            {transactionTypeOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="space-y-1.5">
                    <Label>{t('accounts.transactions_list.flow')}</Label>
                    <Select
                        value={transactionFilters.flow ?? ALL_VALUE}
                        onValueChange={(value) =>
                            navigate({
                                flow:
                                    value === ALL_VALUE
                                        ? null
                                        : (value as AccountTransactionFilters['flow']),
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

                {hasActiveFilters ? (
                    <div className="flex items-end">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                navigate({
                                    search: '',
                                    date_from: null,
                                    date_to: null,
                                    type: null,
                                    flow: null,
                                    page: 1,
                                })
                            }
                        >
                            {t('accounts.transactions_list.reset_filters')}
                        </Button>
                    </div>
                ) : null}
            </div>

            {transactions.data.length === 0 ? (
                <p className="text-muted-foreground text-sm">
                    {hasActiveFilters
                        ? t('accounts.transactions_list.no_results')
                        : t('accounts.no_transactions')}
                </p>
            ) : (
                <>
                    <div className="overflow-x-auto rounded-lg border border-white/10">
                        <table className="w-full min-w-[560px] text-sm">
                            <thead className="text-muted-foreground border-b border-white/10 text-xs uppercase">
                                <tr>
                                    <th className="px-3 py-2 text-left">
                                        <button
                                            type="button"
                                            className="hover:text-foreground inline-flex items-center font-medium uppercase"
                                            onClick={() => handleSort('date')}
                                        >
                                            {t('transactions.date')}
                                            <SortIcon
                                                column="date"
                                                sort={transactionFilters.sort}
                                                order={transactionFilters.order}
                                            />
                                        </button>
                                    </th>
                                    <th className="px-3 py-2 text-left">
                                        <button
                                            type="button"
                                            className="hover:text-foreground inline-flex items-center font-medium uppercase"
                                            onClick={() => handleSort('label')}
                                        >
                                            {t('transactions.label')}
                                            <SortIcon
                                                column="label"
                                                sort={transactionFilters.sort}
                                                order={transactionFilters.order}
                                            />
                                        </button>
                                    </th>
                                    <th className="px-3 py-2 text-left">
                                        <button
                                            type="button"
                                            className="hover:text-foreground inline-flex items-center font-medium uppercase"
                                            onClick={() => handleSort('type')}
                                        >
                                            {t('transactions.type')}
                                            <SortIcon
                                                column="type"
                                                sort={transactionFilters.sort}
                                                order={transactionFilters.order}
                                            />
                                        </button>
                                    </th>
                                    <th className="px-3 py-2 text-right">
                                        <button
                                            type="button"
                                            className={cn(
                                                'hover:text-foreground ml-auto inline-flex items-center font-medium uppercase',
                                            )}
                                            onClick={() => handleSort('amount')}
                                        >
                                            {t('transactions.amount')}
                                            <SortIcon
                                                column="amount"
                                                sort={transactionFilters.sort}
                                                order={transactionFilters.order}
                                            />
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {transactions.data.map((transaction) => (
                                    <tr
                                        key={transaction.id}
                                        className="border-b border-white/5 last:border-0"
                                    >
                                        <td className="text-muted-foreground px-3 py-2 tabular-nums">
                                            {transaction.date}
                                        </td>
                                        <td className="px-3 py-2">
                                            <Link
                                                href={`/transactions/${transaction.id}/edit`}
                                                className="hover:underline"
                                            >
                                                {transaction.label}
                                            </Link>
                                        </td>
                                        <td className="px-3 py-2">
                                            <TransactionTypeBadge type={transaction.type} />
                                        </td>
                                        <td className="px-3 py-2 text-right font-mono tabular-nums">
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
                                size="sm"
                                disabled={meta.current_page <= 1}
                                onClick={() =>
                                    navigate({ page: meta.current_page - 1 })
                                }
                            >
                                <ChevronLeft className="size-4" />
                                {t('accounts.transactions_list.previous')}
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
                                size="sm"
                                disabled={meta.current_page >= meta.last_page}
                                onClick={() =>
                                    navigate({ page: meta.current_page + 1 })
                                }
                            >
                                {t('accounts.transactions_list.next')}
                                <ChevronRight className="size-4" />
                            </Button>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
