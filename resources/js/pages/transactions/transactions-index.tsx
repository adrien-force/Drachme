import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';

import { EntityLogo } from '@/components/entity-logo';
import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { TransactionTypeBadge } from '@/components/transactions/transaction-type-badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import type { TransactionsIndexPageProps } from '@/types/transaction.types';

export default function TransactionsIndex({
    transactions,
}: TransactionsIndexPageProps) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('transactions.title')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {t('transactions.title')}
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {t('transactions.description')}
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/transactions/create">
                            <Plus className="mr-2 size-4" />
                            {t('transactions.create')}
                        </Link>
                    </Button>
                </div>

                {transactions.length === 0 ? (
                    <FadeIn>
                        <GlassPanel className="p-8 text-center">
                            <p className="text-muted-foreground text-sm">
                                {t('accounts.no_transactions')}
                            </p>
                        </GlassPanel>
                    </FadeIn>
                ) : (
                    <FadeIn>
                        <GlassPanel className="overflow-x-auto p-0">
                            <table className="w-full min-w-[720px] text-sm">
                                <thead>
                                    <tr className="text-muted-foreground border-border/60 border-b text-left text-xs uppercase tracking-wide">
                                        <th className="px-4 py-3 font-medium">
                                            {t('transactions.date')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('transactions.account')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('transactions.label')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('transactions.type')}
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            {t('transactions.amount')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {transactions.map((transaction) => (
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
                                                        name={
                                                            transaction.account_name ??
                                                            ''
                                                        }
                                                        logoUrl={
                                                            transaction.account_logo_url
                                                        }
                                                        className="size-5"
                                                    />
                                                    {transaction.account_name}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={`/transactions/${transaction.id}/edit`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {transaction.label}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3">
                                                <TransactionTypeBadge
                                                    type={transaction.type}
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
                        </GlassPanel>
                    </FadeIn>
                )}
            </div>
        </>
    );
}

TransactionsIndex.layout = {
    breadcrumbs: [{ title: 'Transactions', href: '/transactions' }],
};
