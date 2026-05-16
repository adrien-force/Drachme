import { Head } from '@inertiajs/react';
import { Info } from 'lucide-react';

import { CashflowChart } from '@/components/dashboard/cashflow-chart';
import { NetWorthChart } from '@/components/dashboard/net-worth-chart';
import { GlassPanel } from '@/components/glass-panel';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatCurrency, formatPercent } from '@/lib/format-currency';
import { dashboard } from '@/routes';
import type { DashboardPageProps } from '@/types/dashboard.types';

export default function DashboardIndex({
    kpis,
    netWorthHistory,
    cashflow,
    isDemoData,
}: DashboardPageProps) {
    const cashflowPositive = kpis.monthly_cashflow >= 0;

    return (
        <>
            <Head title="Tableau de bord" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                {isDemoData && (
                    <GlassPanel className="animate-in fade-in flex items-start gap-3 border-dashed p-4 duration-500 fill-mode-both">
                        <Info className="text-primary mt-0.5 size-5 shrink-0" />
                        <div>
                            <p className="font-medium">Données de démonstration</p>
                            <p className="text-muted-foreground text-sm">
                                Ces graphiques utilisent des données factices. Les chiffres réels
                                seront branchés avec vos comptes (prochaine phase).
                            </p>
                        </div>
                    </GlassPanel>
                )}

                <div className="grid gap-4 md:grid-cols-3">
                    <GlassPanel className="animate-in fade-in p-0 duration-500 fill-mode-both delay-75">
                        <Card className="border-0 bg-transparent shadow-none">
                            <CardHeader className="pb-2">
                                <CardDescription>Patrimoine net</CardDescription>
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
                                    {formatPercent(kpis.net_worth_change_pct)} ce mois
                                </p>
                            </CardContent>
                        </Card>
                    </GlassPanel>

                    <GlassPanel className="animate-in fade-in p-0 duration-500 fill-mode-both delay-100">
                        <Card className="border-0 bg-transparent shadow-none">
                            <CardHeader className="pb-2">
                                <CardDescription>Variation mensuelle</CardDescription>
                                <CardTitle className="text-2xl tabular-nums">
                                    {formatPercent(kpis.net_worth_change_pct)}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-muted-foreground text-sm">
                                    Sur la base du dernier mois enregistré
                                </p>
                            </CardContent>
                        </Card>
                    </GlassPanel>

                    <GlassPanel className="animate-in fade-in p-0 duration-500 fill-mode-both delay-150">
                        <Card className="border-0 bg-transparent shadow-none">
                            <CardHeader className="pb-2">
                                <CardDescription>Cashflow du mois</CardDescription>
                                <CardTitle
                                    className={`text-2xl tabular-nums ${cashflowPositive ? 'text-primary' : 'text-destructive'}`}
                                >
                                    {formatCurrency(kpis.monthly_cashflow, { precise: true })}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-muted-foreground text-sm">
                                    Revenus − dépenses (mois en cours)
                                </p>
                            </CardContent>
                        </Card>
                    </GlassPanel>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <NetWorthChart data={netWorthHistory} />
                    <CashflowChart data={cashflow} />
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
