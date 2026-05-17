import { Head, Link, router } from '@inertiajs/react';
import { Plus, RefreshCw, TrendingUp } from 'lucide-react';
import { useState } from 'react';

import { EntityLogo } from '@/components/entity-logo';
import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { PortfolioImportHistory } from '@/components/investments/portfolio-import-history';
import { formatCurrency } from '@/lib/format-currency';
import { refreshPrices as refreshPricesRoute } from '@/routes/investments';
import type { InvestmentsIndexPageProps } from '@/types/position.types';

export default function InvestmentsIndex({
    accounts,
    marketDataConfigured,
}: InvestmentsIndexPageProps) {
    const { t } = useTranslation();
    const [refreshingPrices, setRefreshingPrices] = useState(false);

    return (
        <>
            <Head title={t('investments.title')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <FadeIn>
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-start gap-3">
                            <div className="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-lg">
                                <TrendingUp className="size-5" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-semibold tracking-tight">
                                    {t('investments.title')}
                                </h1>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {t('investments.description')}
                                </p>
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {accounts.length > 0 ? (
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={!marketDataConfigured || refreshingPrices}
                                    title={
                                        marketDataConfigured
                                            ? undefined
                                            : t('investments.market_data_not_configured')
                                    }
                                    onClick={() => {
                                        setRefreshingPrices(true);
                                        router.post(
                                            refreshPricesRoute.url(),
                                            {},
                                            {
                                                preserveScroll: true,
                                                onFinish: () => setRefreshingPrices(false),
                                            },
                                        );
                                    }}
                                >
                                    <RefreshCw
                                        className={`mr-2 size-4 ${refreshingPrices ? 'animate-spin' : ''}`}
                                    />
                                    {t('investments.refresh_prices')}
                                </Button>
                            ) : null}
                            <Button asChild>
                                <Link href="/accounts/create">
                                    <Plus className="mr-2 size-4" />
                                    {t('investments.create_invest_account')}
                                </Link>
                            </Button>
                        </div>
                    </div>
                </FadeIn>

                {accounts.length === 0 ? (
                    <FadeIn>
                        <GlassPanel className="p-8 text-center">
                            <p className="text-muted-foreground text-sm">
                                {t('investments.empty')}
                            </p>
                        </GlassPanel>
                    </FadeIn>
                ) : (
                    <div className="grid gap-4">
                        {accounts.map((account, index) => (
                            <FadeIn key={account.id} delay={index * 0.05}>
                                <GlassPanel className="p-4 md:p-5">
                                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="flex items-center gap-3">
                                            <EntityLogo
                                                name={account.name}
                                                logoUrl={account.logo_url}
                                                className="size-10"
                                            />
                                            <div>
                                                <h2 className="font-medium">{account.name}</h2>
                                                {account.institution && (
                                                    <p className="text-muted-foreground text-sm">
                                                        {account.institution}
                                                    </p>
                                                )}
                                                <p className="text-muted-foreground mt-1 text-xs">
                                                    {t('investments.positions_count', {
                                                        count: account.positions_count,
                                                    })}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex flex-col gap-1 text-sm sm:text-right">
                                            <div>
                                                <span className="text-muted-foreground">
                                                    {t('investments.positions_value')}:{' '}
                                                </span>
                                                <span className="font-medium tabular-nums">
                                                    {formatCurrency(account.positions_value, {
                                                        precise: true,
                                                    })}
                                                </span>
                                            </div>
                                            <div>
                                                <span className="text-muted-foreground">
                                                    {t('investments.cash_balance')}:{' '}
                                                </span>
                                                <span className="tabular-nums">
                                                    {formatCurrency(account.current_balance, {
                                                        precise: true,
                                                    })}
                                                </span>
                                            </div>
                                        </div>
                                        <Button asChild variant="outline" size="sm">
                                            <Link href={`/accounts/${account.id}/positions`}>
                                                {t('investments.open_positions')}
                                            </Link>
                                        </Button>
                                    </div>
                                    <PortfolioImportHistory
                                        accountName={account.name}
                                        entries={account.import_history}
                                    />
                                </GlassPanel>
                            </FadeIn>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
