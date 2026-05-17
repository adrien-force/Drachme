import { Head } from '@inertiajs/react';
import { Info } from 'lucide-react';
import { isValidElement, type ReactNode } from 'react';

import { AccountAllocationChart } from '@/components/dashboard/account-allocation-chart';
import { CashflowChart } from '@/components/dashboard/cashflow-chart';
import { DashboardDateRangePicker } from '@/components/dashboard/dashboard-date-range-picker';
import { NetWorthChart } from '@/components/dashboard/net-worth-chart';
import { PortfolioEvolutionChart } from '@/components/dashboard/portfolio-evolution-chart';
import { GlassPanel } from '@/components/glass-panel';
import AppLayout from '@/layouts/app-layout';
import { FadeIn } from '@/components/motion/fade-in';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency, formatPercent } from '@/lib/format-currency';
import { dashboard } from '@/routes';
import type { DashboardPageProps } from '@/types/dashboard.types';

function DashboardPage({
    dateRange,
    kpis,
    netWorthHistory,
    portfolioHistory,
    accountAllocation,
    cashflow,
    isDemoData,
}: DashboardPageProps) {
    const { t } = useTranslation();
    const cashflowPositive = kpis.monthly_cashflow >= 0;
    const showPortfolioKpi = kpis.portfolio_value > 0 || portfolioHistory.length > 0;
    const portfolioPositive = kpis.portfolio_change_pct >= 0;

    return (
        <>
            <Head title={t('dashboard.title')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                {isDemoData && (
                    <FadeIn>
                        <GlassPanel className="flex items-start gap-3 border-dashed p-4">
                            <Info className="text-primary mt-0.5 size-5 shrink-0" />
                            <div>
                                <p className="font-medium">
                                    {t('dashboard.demo_banner_title')}
                                </p>
                                <p className="text-muted-foreground text-sm">
                                    {t('dashboard.demo_banner_body')}
                                </p>
                            </div>
                        </GlassPanel>
                    </FadeIn>
                )}

                <div
                    className={`grid auto-rows-fr gap-4 ${showPortfolioKpi ? 'md:grid-cols-2 lg:grid-cols-4' : 'md:grid-cols-3'}`}
                >
                    <FadeIn delay={0.05} className="h-full">
                        <GlassPanel className="h-full p-0">
                            <Card className="flex h-full flex-col border-0 bg-transparent shadow-none">
                                <CardHeader className="pb-2">
                                    <CardDescription>
                                        {t('dashboard.net_worth')}
                                    </CardDescription>
                                    <CardTitle className="text-2xl tabular-nums">
                                        {formatCurrency(kpis.net_worth)}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="mt-auto">
                                    <p
                                        className={
                                            kpis.net_worth_change_pct >= 0
                                                ? 'text-primary text-sm font-medium'
                                                : 'text-destructive text-sm font-medium'
                                        }
                                    >
                                        {t('dashboard.net_worth_change', {
                                            value: formatPercent(
                                                kpis.net_worth_change_pct,
                                            ),
                                        })}
                                    </p>
                                </CardContent>
                            </Card>
                        </GlassPanel>
                    </FadeIn>

                    {showPortfolioKpi ? (
                        <FadeIn delay={0.08} className="h-full">
                            <GlassPanel className="h-full p-0">
                                <Card className="flex h-full flex-col border-0 bg-transparent shadow-none">
                                    <CardHeader className="pb-2">
                                        <CardDescription>
                                            {t('dashboard.portfolio_value')}
                                        </CardDescription>
                                        <CardTitle className="text-2xl tabular-nums">
                                            {formatCurrency(kpis.portfolio_value)}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="mt-auto">
                                        {portfolioHistory.length >= 2 ? (
                                            <p
                                                className={
                                                    portfolioPositive
                                                        ? 'text-primary text-sm font-medium'
                                                        : 'text-destructive text-sm font-medium'
                                                }
                                            >
                                                {t('dashboard.portfolio_change', {
                                                    value: formatPercent(
                                                        kpis.portfolio_change_pct,
                                                    ),
                                                })}
                                            </p>
                                        ) : (
                                            <p
                                                className="text-muted-foreground text-sm"
                                                aria-hidden
                                            >
                                                {'\u00a0'}
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>
                            </GlassPanel>
                        </FadeIn>
                    ) : null}

                    <FadeIn delay={0.1} className="h-full">
                        <GlassPanel className="h-full p-0">
                            <Card className="flex h-full flex-col border-0 bg-transparent shadow-none">
                                <CardHeader className="pb-2">
                                    <CardDescription>
                                        {t('dashboard.total_assets')}
                                    </CardDescription>
                                    <CardTitle className="text-2xl tabular-nums">
                                        {formatCurrency(kpis.total_assets)}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="mt-auto">
                                    <p className="text-muted-foreground text-sm">
                                        {t('dashboard.total_assets_hint')}
                                    </p>
                                </CardContent>
                            </Card>
                        </GlassPanel>
                    </FadeIn>

                    <FadeIn delay={0.15} className="h-full">
                        <GlassPanel className="h-full p-0">
                            <Card className="flex h-full flex-col border-0 bg-transparent shadow-none">
                                <CardHeader className="pb-2">
                                    <CardDescription>
                                        {t('dashboard.monthly_cashflow')}
                                    </CardDescription>
                                    <CardTitle
                                        className={`text-2xl tabular-nums ${cashflowPositive ? 'text-primary' : 'text-destructive'}`}
                                    >
                                        {formatCurrency(kpis.monthly_cashflow, {
                                            precise: true,
                                        })}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="mt-auto">
                                    <p className="text-muted-foreground text-sm">
                                        {t('dashboard.monthly_cashflow_hint')}
                                    </p>
                                </CardContent>
                            </Card>
                        </GlassPanel>
                    </FadeIn>
                </div>

                <div className="grid auto-rows-fr gap-4 lg:grid-cols-2">
                    <FadeIn delay={0.2} className="h-full">
                        <NetWorthChart data={netWorthHistory} dateRange={dateRange} />
                    </FadeIn>
                    <FadeIn delay={0.25} className="h-full">
                        <CashflowChart data={cashflow} dateRange={dateRange} />
                    </FadeIn>
                    {accountAllocation.length > 0 ? (
                        <FadeIn delay={0.28} className="h-full">
                            <AccountAllocationChart data={accountAllocation} />
                        </FadeIn>
                    ) : null}
                    {portfolioHistory.length > 0 ? (
                        <FadeIn
                            delay={0.3}
                            className={`h-full ${accountAllocation.length > 0 ? '' : 'lg:col-span-2'}`}
                        >
                            <PortfolioEvolutionChart data={portfolioHistory} />
                        </FadeIn>
                    ) : null}
                </div>
            </div>
        </>
    );
}

export default function DashboardIndex(props: DashboardPageProps) {
    return <DashboardPage {...props} />;
}

const dashboardBreadcrumbs = [
    {
        title: 'Tableau de bord',
        href: dashboard(),
    },
];

function dateRangeFromLayoutArg(page: ReactNode): DashboardPageProps['dateRange'] {
    if (isValidElement(page)) {
        return (page.props as DashboardPageProps).dateRange;
    }

    return (page as unknown as DashboardPageProps).dateRange;
}

DashboardIndex.layout = (page: ReactNode) => (
    <AppLayout
        breadcrumbs={dashboardBreadcrumbs}
        headerEnd={
            <DashboardDateRangePicker dateRange={dateRangeFromLayoutArg(page)} />
        }
    >
        {page}
    </AppLayout>
);
