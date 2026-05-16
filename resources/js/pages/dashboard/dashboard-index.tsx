import { Head } from '@inertiajs/react';
import { Info } from 'lucide-react';

import { CashflowChart } from '@/components/dashboard/cashflow-chart';
import { NetWorthChart } from '@/components/dashboard/net-worth-chart';
import { GlassPanel } from '@/components/glass-panel';
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

export default function DashboardIndex({
    kpis,
    netWorthHistory,
    cashflow,
    isDemoData,
}: DashboardPageProps) {
    const { t } = useTranslation();
    const cashflowPositive = kpis.monthly_cashflow >= 0;

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

                <div className="grid gap-4 md:grid-cols-3">
                    <FadeIn delay={0.05}>
                        <GlassPanel className="p-0">
                            <Card className="border-0 bg-transparent shadow-none">
                                <CardHeader className="pb-2">
                                    <CardDescription>
                                        {t('dashboard.net_worth')}
                                    </CardDescription>
                                    <CardTitle className="text-2xl tabular-nums">
                                        {formatCurrency(kpis.net_worth)}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
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

                    <FadeIn delay={0.1}>
                        <GlassPanel className="p-0">
                            <Card className="border-0 bg-transparent shadow-none">
                                <CardHeader className="pb-2">
                                    <CardDescription>
                                        {t('dashboard.monthly_change')}
                                    </CardDescription>
                                    <CardTitle className="text-2xl tabular-nums">
                                        {formatPercent(kpis.net_worth_change_pct)}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-muted-foreground text-sm">
                                        {t('dashboard.monthly_change_hint')}
                                    </p>
                                </CardContent>
                            </Card>
                        </GlassPanel>
                    </FadeIn>

                    <FadeIn delay={0.15}>
                        <GlassPanel className="p-0">
                            <Card className="border-0 bg-transparent shadow-none">
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
                                <CardContent>
                                    <p className="text-muted-foreground text-sm">
                                        {t('dashboard.monthly_cashflow_hint')}
                                    </p>
                                </CardContent>
                            </Card>
                        </GlassPanel>
                    </FadeIn>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <FadeIn delay={0.2}>
                        <NetWorthChart data={netWorthHistory} />
                    </FadeIn>
                    <FadeIn delay={0.25}>
                        <CashflowChart data={cashflow} />
                    </FadeIn>
                </div>
            </div>
        </>
    );
}

DashboardIndex.layout = {
    breadcrumbs: [
        {
            title: 'Tableau de bord',
            href: dashboard(),
        },
    ],
};
