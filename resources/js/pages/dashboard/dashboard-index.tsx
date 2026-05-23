import { Head } from '@inertiajs/react';
import { Info } from 'lucide-react';
import { isValidElement, type ReactNode } from 'react';

import { AccountAllocationChart } from '@/components/dashboard/account-allocation-chart';
import { CashflowChart } from '@/components/dashboard/cashflow-chart';
import { DashboardDateRangePicker } from '@/components/dashboard/dashboard-date-range-picker';
import { DashboardKpiCard } from '@/components/dashboard/dashboard-kpi-card';
import { NetWorthChart } from '@/components/dashboard/net-worth-chart';
import { PortfolioEvolutionChart } from '@/components/dashboard/portfolio-evolution-chart';
import { GlassPanel } from '@/components/glass-panel';
import AppLayout from '@/layouts/app-layout';
import { FadeIn } from '@/components/motion/fade-in';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency, formatPercent } from '@/lib/format-currency';
import {
    DASHBOARD_ROWS_DEFAULT,
    DASHBOARD_ROWS_WITH_BANNER,
    dashboardChartsGridClass,
} from '@/lib/dashboard-layout';
import { cn } from '@/lib/utils';
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

    const chartPanelCount =
        2 +
        (accountAllocation.length > 0 ? 1 : 0) +
        (portfolioHistory.length > 0 ? 1 : 0);

    const kpiColumnClass = showPortfolioKpi
        ? 'md:grid-cols-2 lg:grid-cols-4'
        : 'md:grid-cols-3';

    return (
        <>
            <Head title={t('dashboard.title')} />

            <div
                className={cn(
                    'grid h-full min-h-0 flex-1 gap-3 overflow-hidden p-4 md:gap-4 md:p-6',
                    isDemoData ? DASHBOARD_ROWS_WITH_BANNER : DASHBOARD_ROWS_DEFAULT,
                )}
            >
                {isDemoData ? (
                    <FadeIn className="min-h-0 shrink-0">
                        <GlassPanel className="flex items-start gap-3 border-dashed p-3 md:p-4">
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
                ) : null}

                <div
                    className={cn(
                        'grid shrink-0 items-stretch gap-3 md:gap-4',
                        kpiColumnClass,
                    )}
                >
                    <FadeIn delay={0.05} className="h-full">
                        <DashboardKpiCard
                            label={t('dashboard.net_worth')}
                            value={formatCurrency(kpis.net_worth)}
                            footnote={
                                <p
                                    className={
                                        kpis.net_worth_change_pct >= 0
                                            ? 'text-primary font-medium'
                                            : 'text-destructive font-medium'
                                    }
                                >
                                    {t('dashboard.net_worth_change', {
                                        value: formatPercent(kpis.net_worth_change_pct),
                                    })}
                                </p>
                            }
                        />
                    </FadeIn>

                    {showPortfolioKpi ? (
                        <FadeIn delay={0.08} className="h-full">
                            <DashboardKpiCard
                                label={t('dashboard.portfolio_value')}
                                value={formatCurrency(kpis.portfolio_value)}
                                footnote={
                                    portfolioHistory.length >= 2 ? (
                                        <p
                                            className={
                                                portfolioPositive
                                                    ? 'text-primary font-medium'
                                                    : 'text-destructive font-medium'
                                            }
                                        >
                                            {t('dashboard.portfolio_change', {
                                                value: formatPercent(
                                                    kpis.portfolio_change_pct,
                                                ),
                                            })}
                                        </p>
                                    ) : (
                                        <span className="text-muted-foreground" aria-hidden>
                                            {'\u00a0'}
                                        </span>
                                    )
                                }
                            />
                        </FadeIn>
                    ) : null}

                    <FadeIn delay={0.1} className="h-full">
                        <DashboardKpiCard
                            label={t('dashboard.total_assets')}
                            value={formatCurrency(kpis.total_assets)}
                            footnote={
                                <p className="text-muted-foreground">
                                    {t('dashboard.total_assets_hint')}
                                </p>
                            }
                        />
                    </FadeIn>

                    <FadeIn delay={0.15} className="h-full">
                        <DashboardKpiCard
                            label={t('dashboard.monthly_cashflow')}
                            value={formatCurrency(kpis.monthly_cashflow, { precise: true })}
                            valueClassName={
                                cashflowPositive ? 'text-primary' : 'text-destructive'
                            }
                            footnote={
                                <p className="text-muted-foreground">
                                    {t('dashboard.monthly_cashflow_hint')}
                                </p>
                            }
                        />
                    </FadeIn>
                </div>

                <div className={dashboardChartsGridClass(chartPanelCount)}>
                    <FadeIn delay={0.2} className="h-full min-h-0 overflow-hidden">
                        <NetWorthChart data={netWorthHistory} dateRange={dateRange} />
                    </FadeIn>
                    <FadeIn delay={0.25} className="h-full min-h-0 overflow-hidden">
                        <CashflowChart data={cashflow} dateRange={dateRange} />
                    </FadeIn>
                    {accountAllocation.length > 0 ? (
                        <FadeIn delay={0.28} className="h-full min-h-0 overflow-hidden">
                            <AccountAllocationChart data={accountAllocation} />
                        </FadeIn>
                    ) : null}
                    {portfolioHistory.length > 0 ? (
                        <FadeIn
                            delay={0.3}
                            className={cn(
                                'h-full min-h-0 overflow-hidden',
                                accountAllocation.length === 0 ? 'lg:col-span-2' : '',
                            )}
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
        titleKey: 'nav.dashboard',
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
        <div className="flex h-[calc(100dvh-5.25rem)] max-h-[calc(100dvh-5.25rem)] min-h-0 flex-1 flex-col overflow-hidden">
            {page}
        </div>
    </AppLayout>
);
