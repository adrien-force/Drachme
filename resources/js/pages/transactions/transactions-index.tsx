import { Head, Link, router } from '@inertiajs/react';
import { Plus, Sparkles } from 'lucide-react';

import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { TransactionEditModal } from '@/components/transactions/transaction-edit-modal';
import { TransactionsIndexPanel } from '@/components/transactions/transactions-index-panel';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { buildTransactionsIndexQuery } from '@/lib/transactions-index-query';
import type { TransactionsIndexPageProps } from '@/types/transaction.types';

export default function TransactionsIndex({
    transactions,
    listSummary,
    sankeyFlow,
    categoryOptions,
    accountOptions,
    filters,
    transactionEdit,
    uncategorizedCount,
    perPageOptions,
    typeOptions,
}: TransactionsIndexPageProps) {
    const { t } = useTranslation();

    const closeTransactionEdit = () => {
        router.get('/transactions', buildTransactionsIndexQuery(filters), {
            preserveScroll: true,
            replace: true,
        });
    };

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
                    <div className="flex flex-wrap gap-2">
                        {uncategorizedCount > 0 ? (
                            <Button asChild variant="default">
                                <Link href="/transactions/triage">
                                    <Sparkles className="mr-2 size-4" />
                                    {t('transactions.triage.title')}
                                </Link>
                            </Button>
                        ) : null}
                        <Button asChild variant="outline">
                            <Link href="/transactions/create">
                                <Plus className="mr-2 size-4" />
                                {t('transactions.create')}
                            </Link>
                        </Button>
                    </div>
                </div>

                <FadeIn>
                    <GlassPanel className="p-4 md:p-6">
                        <TransactionsIndexPanel
                            transactions={transactions}
                            listSummary={listSummary}
                            sankeyFlow={sankeyFlow}
                            filters={filters}
                            categoryOptions={categoryOptions}
                            accountOptions={accountOptions}
                            typeOptions={typeOptions}
                            perPageOptions={perPageOptions}
                            uncategorizedCount={uncategorizedCount}
                        />
                    </GlassPanel>
                </FadeIn>
            </div>

            {transactionEdit?.transaction ? (
                <TransactionEditModal
                    open
                    onClose={closeTransactionEdit}
                    {...transactionEdit}
                />
            ) : null}
        </>
    );
}

TransactionsIndex.layout = {
    breadcrumbs: [{ title: 'Transactions', href: '/transactions' }],
};
