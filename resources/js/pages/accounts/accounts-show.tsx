import { Head, Link, router } from '@inertiajs/react';
import { Archive, ArrowLeft, Pencil, Plus, Upload } from 'lucide-react';

import { AccountBalanceChart } from '@/components/accounts/account-balance-chart';
import { AccountBalanceDateRange } from '@/components/accounts/account-balance-date-range';
import { AccountTransactionsPanel } from '@/components/accounts/account-transactions-panel';
import { AccountTypeBadge } from '@/components/accounts/account-type-badge';
import { EntityLogo } from '@/components/entity-logo';
import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { TransactionEditModal } from '@/components/transactions/transaction-edit-modal';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { buildAccountShowQuery } from '@/lib/account-show-query';
import { formatCurrency } from '@/lib/format-currency';
import type { AccountsShowPageProps } from '@/types/account.types';

export default function AccountsShow({
    account,
    transactions,
    transactionFilters,
    transactionTypeOptions,
    categoryOptions,
    perPageOptions,
    balanceHistory,
    transactionEdit,
    uncategorizedCount,
}: AccountsShowPageProps) {
    const { t } = useTranslation();

    const closeTransactionEdit = () => {
        router.get(
            `/accounts/${account.id}`,
            buildAccountShowQuery(balanceHistory, transactionFilters),
            { preserveScroll: true, replace: true },
        );
    };

    const archiveAccount = () => {
        if (!window.confirm(t('accounts.archive_confirm', { name: account.name }))) {
            return;
        }

        router.post(`/accounts/${account.id}/archive`);
    };

    return (
        <>
            <Head title={account.name} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4">
                    <Button asChild variant="ghost" size="sm" className="w-fit px-2">
                        <Link href="/accounts">
                            <ArrowLeft className="mr-2 size-4" />
                            {t('accounts.back_to_list')}
                        </Link>
                    </Button>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div className="flex flex-wrap items-center gap-3">
                                <EntityLogo
                                    name={account.name}
                                    logoUrl={account.logo_url}
                                    className="size-12"
                                />
                                <h1 className="text-2xl font-semibold tracking-tight">
                                    {account.name}
                                </h1>
                                <AccountTypeBadge type={account.type} />
                                {account.is_archived && (
                                    <span className="text-muted-foreground text-sm">
                                        {t('accounts.archived_label')}
                                    </span>
                                )}
                            </div>
                            {account.institution && (
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {account.institution}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {account.type === 'invest' && (
                                <Button asChild variant="outline" size="sm">
                                    <Link href={`/accounts/${account.id}/positions`}>
                                        {t('accounts.view_positions')}
                                    </Link>
                                </Button>
                            )}
                            <Button asChild variant="outline" size="sm">
                                <Link
                                    href={`/transactions/create?account_id=${account.id}`}
                                >
                                    <Plus className="mr-2 size-4" />
                                    {t('accounts.add_transaction')}
                                </Link>
                            </Button>
                            <Button asChild variant="outline" size="sm">
                                <Link href="/import">
                                    <Upload className="mr-2 size-4" />
                                    {t('accounts.import')}
                                </Link>
                            </Button>
                            <Button asChild size="sm">
                                <Link href={`/accounts/${account.id}/edit`}>
                                    <Pencil className="mr-2 size-4" />
                                    {t('accounts.edit')}
                                </Link>
                            </Button>
                            {!account.is_archived ? (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={archiveAccount}
                                >
                                    <Archive className="mr-2 size-4" />
                                    {t('accounts.archive')}
                                </Button>
                            ) : null}
                        </div>
                    </div>
                </div>

                <FadeIn>
                    <GlassPanel className="p-6">
                        <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">
                            {t('accounts.current_balance')}
                        </p>
                        <p className="mt-2 text-3xl font-semibold tabular-nums">
                            {formatCurrency(account.current_balance, { precise: true })}
                        </p>
                    </GlassPanel>
                </FadeIn>

                <FadeIn delay={0.03}>
                    <GlassPanel className="space-y-4 p-6">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-lg font-semibold">
                                    {t('accounts.balance_chart.title')}
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    {t('accounts.balance_chart.description')}
                                </p>
                            </div>
                            <AccountBalanceDateRange
                                accountId={account.id}
                                balanceHistory={balanceHistory}
                                transactionFilters={transactionFilters}
                            />
                        </div>
                        <AccountBalanceChart points={balanceHistory.points} />
                    </GlassPanel>
                </FadeIn>

                <FadeIn delay={0.05}>
                    <GlassPanel className="p-6">
                        <AccountTransactionsPanel
                            accountId={account.id}
                            transactions={transactions}
                            transactionFilters={transactionFilters}
                            transactionTypeOptions={transactionTypeOptions}
                            categoryOptions={categoryOptions}
                            perPageOptions={perPageOptions}
                            balanceHistory={balanceHistory}
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

AccountsShow.layout = {
    breadcrumbs: [{ title: 'Comptes', href: '/accounts' }],
};
