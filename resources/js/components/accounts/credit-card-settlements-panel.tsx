import { Link, router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { format, parseISO } from 'date-fns';
import { enUS, fr } from 'date-fns/locale';
import { useMemo, useState } from 'react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';

import { CreditCardSettlementDetailSheet } from '@/components/accounts/credit-card-settlement-detail-sheet';
import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { Button } from '@/components/ui/button';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import type {
    CreditCardSettlement,
    CreditCardSettlementsData,
} from '@/types/account.types';

type ChartRow = {
    id: number;
    sortKey: string;
    label: string;
    amount: number;
    settlement: CreditCardSettlement;
};

type CreditCardSettlementsPanelProps = {
    accountId: number;
    data: CreditCardSettlementsData;
};

export function CreditCardSettlementsPanel({ accountId, data }: CreditCardSettlementsPanelProps) {
    const { t, locale } = useTranslation();
    const dateLocale = locale === 'fr' ? fr : enUS;

    const [selectedSettlement, setSelectedSettlement] = useState<CreditCardSettlement | null>(
        null,
    );
    const [openPeriodOpen, setOpenPeriodOpen] = useState(false);
    const [syncing, setSyncing] = useState(false);

    const runSync = () => {
        setSyncing(true);
        router.post(
            `/accounts/${accountId}/sync-settlements`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setSyncing(false),
            },
        );
    };

    const chartConfig = {
        amount: {
            label: t('accounts.credit_card_settlements.chart_label'),
            color: 'var(--chart-net-worth)',
        },
    } satisfies ChartConfig;

    const settlementsByDate = useMemo(
        () => [...data.settlements].sort((a, b) => b.date.localeCompare(a.date)),
        [data.settlements],
    );

    const chartRows = useMemo((): ChartRow[] => {
        return [...settlementsByDate]
            .sort((a, b) => a.date.localeCompare(b.date))
            .map((settlement) => ({
                id: settlement.id,
                sortKey: settlement.date,
                label: format(parseISO(settlement.date), 'MMM yy', { locale: dateLocale }),
                amount: settlement.amount,
                settlement,
            }));
    }, [settlementsByDate, dateLocale]);

    const handleBarClick = (
        state: {
            activePayload?: Array<{
                payload?: ChartRow;
            }>;
        } | null,
    ) => {
        const row = state?.activePayload?.[0]?.payload;

        if (row?.settlement) {
            setSelectedSettlement(row.settlement);
        }
    };

    return (
        <>
            {data.open_period !== null ? (
                <FadeIn>
                    <button
                        type="button"
                        onClick={() => setOpenPeriodOpen(true)}
                        className="border-primary/30 bg-primary/5 hover:bg-primary/10 w-full rounded-lg border p-4 text-left transition-colors"
                    >
                        <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">
                            {t('accounts.credit_card_settlements.open_period_title')}
                        </p>
                        <p className="mt-1 text-2xl font-semibold tabular-nums">
                            {formatCurrency(data.open_period.spend_total, { precise: true })}
                        </p>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {t('accounts.credit_card_settlements.open_period_hint', {
                                count: data.open_period.purchase_count,
                            })}
                        </p>
                    </button>
                </FadeIn>
            ) : null}

            <FadeIn delay={0.03}>
                <GlassPanel className="space-y-4 p-6">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 className="text-lg font-semibold">
                                {t('accounts.credit_card_settlements.chart_title')}
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                {t('accounts.credit_card_settlements.chart_description')}
                            </p>
                            <p className="text-muted-foreground mt-2 text-xs leading-relaxed">
                                {t('accounts.credit_card_settlements.how_it_works')}
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={syncing}
                            onClick={runSync}
                            className="shrink-0"
                        >
                            <RefreshCw
                                className={`mr-2 size-4 ${syncing ? 'animate-spin' : ''}`}
                            />
                            {t('accounts.sync_settlements')}
                        </Button>
                    </div>

                    {data.unmarked_candidates.length > 0 ? (
                        <div className="rounded-lg border border-amber-500/40 bg-amber-500/10 p-3">
                            <p className="text-sm font-medium">
                                {t('accounts.credit_card_settlements.unmarked_title')}
                            </p>
                            <p className="text-muted-foreground mt-1 text-xs">
                                {t('accounts.credit_card_settlements.unmarked_hint')}
                            </p>
                            <ul className="mt-2 space-y-1">
                                {data.unmarked_candidates.map((candidate) => (
                                    <li key={candidate.id}>
                                        <Link
                                            href={`/accounts/${candidate.account_id}?edit_transaction=${candidate.id}`}
                                            className="hover:text-primary text-sm underline-offset-2 hover:underline"
                                        >
                                            {format(parseISO(candidate.date), 'd MMM yyyy', {
                                                locale: dateLocale,
                                            })}{' '}
                                            · {candidate.label} ·{' '}
                                            {formatCurrency(candidate.amount, { precise: true })}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : null}

                    {chartRows.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            {t('accounts.credit_card_settlements.empty')}
                        </p>
                    ) : (
                        <ChartContainer
                            config={chartConfig}
                            className="aspect-auto h-72 w-full [&_.recharts-rectangle.recharts-tooltip-cursor]:fill-muted/15"
                        >
                            <BarChart
                                data={chartRows}
                                margin={{ top: 8, right: 8, left: 0, bottom: 24 }}
                                onClick={handleBarClick}
                                style={{ cursor: 'pointer' }}
                            >
                                <CartesianGrid vertical={false} strokeDasharray="4 4" />
                                <XAxis
                                    dataKey="sortKey"
                                    tickLine={false}
                                    tickFormatter={(value) =>
                                        format(parseISO(String(value)), 'MMM yy', {
                                            locale: dateLocale,
                                        })
                                    }
                                    axisLine={false}
                                    tickMargin={8}
                                    minTickGap={16}
                                />
                                <YAxis
                                    tickLine={false}
                                    axisLine={false}
                                    tickMargin={8}
                                    width={72}
                                    tickFormatter={(value) => formatCurrency(Number(value))}
                                />
                                <ChartTooltip
                                    content={
                                        <ChartTooltipContent
                                            formatter={(value) =>
                                                formatCurrency(Number(value), { precise: true })
                                            }
                                        />
                                    }
                                />
                                <Bar
                                    dataKey="amount"
                                    fill="var(--color-amount)"
                                    radius={[4, 4, 0, 0]}
                                    maxBarSize={48}
                                />
                            </BarChart>
                        </ChartContainer>
                    )}

                    {data.settlements.length > 0 ? (
                        <ul className="divide-border/60 grid gap-2 sm:grid-cols-2">
                            {settlementsByDate.map((settlement) => (
                                <li key={settlement.id}>
                                    <button
                                        type="button"
                                        onClick={() => setSelectedSettlement(settlement)}
                                        className="hover:bg-muted/40 border-border/60 w-full rounded-lg border px-3 py-2.5 text-left transition-colors"
                                    >
                                        <div className="flex items-center justify-between gap-2">
                                            <span className="text-sm font-medium capitalize">
                                                {format(parseISO(settlement.date), 'MMMM yyyy', {
                                                    locale: dateLocale,
                                                })}
                                            </span>
                                            <span className="text-sm font-semibold tabular-nums">
                                                {formatCurrency(settlement.amount, {
                                                    precise: true,
                                                })}
                                            </span>
                                        </div>
                                        <p className="text-muted-foreground mt-0.5 truncate text-xs">
                                            {settlement.purchase_count}{' '}
                                            {t('accounts.credit_card_settlements.purchases_short')}
                                            ·{' '}
                                            {formatCurrency(settlement.spend_total, {
                                                precise: true,
                                            })}
                                        </p>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    ) : null}
                </GlassPanel>
            </FadeIn>

            <CreditCardSettlementDetailSheet
                detail={selectedSettlement}
                variant="settlement"
                open={selectedSettlement !== null}
                onOpenChange={(next) => {
                    if (!next) {
                        setSelectedSettlement(null);
                    }
                }}
            />

            <CreditCardSettlementDetailSheet
                detail={data.open_period}
                variant="open_period"
                open={openPeriodOpen}
                onOpenChange={setOpenPeriodOpen}
            />
        </>
    );
}
