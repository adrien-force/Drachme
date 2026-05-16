import {
    Bar,
    BarChart,
    CartesianGrid,
    Legend,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatCurrency } from '@/lib/format-currency';
import type { CashflowPoint } from '@/types/dashboard.types';

type CashflowChartProps = {
    data: CashflowPoint[];
};

function ChartTooltip({
    active,
    payload,
    label,
}: {
    active?: boolean;
    label?: string;
    payload?: Array<{ dataKey: string; value: number; color: string }>;
}) {
    if (!active || !payload?.length) {
        return null;
    }

    return (
        <div className="bg-popover text-popover-foreground rounded-lg border px-3 py-2 text-sm shadow-md">
            <p className="text-muted-foreground mb-1">{label}</p>
            {payload.map((entry) => (
                <p key={entry.dataKey} className="font-medium" style={{ color: entry.color }}>
                    {entry.dataKey === 'income' ? 'Revenus' : 'Dépenses'} :{' '}
                    {formatCurrency(entry.value)}
                </p>
            ))}
        </div>
    );
}

export function CashflowChart({ data }: CashflowChartProps) {
    return (
        <Card className="animate-in fade-in duration-500 fill-mode-both delay-150">
            <CardHeader>
                <CardTitle>Revenus et dépenses</CardTitle>
                <CardDescription>Cashflow mensuel</CardDescription>
            </CardHeader>
            <CardContent>
                <div className="h-72 w-full">
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart
                            data={data}
                            margin={{ top: 8, right: 8, left: 0, bottom: 0 }}
                        >
                            <CartesianGrid
                                stroke="var(--border)"
                                strokeDasharray="4 4"
                                vertical={false}
                            />
                            <XAxis
                                dataKey="label"
                                tick={{ fill: 'var(--muted-foreground)', fontSize: 11 }}
                                tickLine={false}
                                axisLine={false}
                                interval="preserveStartEnd"
                            />
                            <YAxis
                                tickFormatter={(v) => formatCurrency(Number(v))}
                                tick={{ fill: 'var(--muted-foreground)', fontSize: 11 }}
                                tickLine={false}
                                axisLine={false}
                                width={72}
                            />
                            <Tooltip content={<ChartTooltip />} />
                            <Legend
                                formatter={(value) =>
                                    value === 'income' ? 'Revenus' : 'Dépenses'
                                }
                            />
                            <Bar
                                dataKey="income"
                                name="income"
                                fill="var(--primary)"
                                radius={[4, 4, 0, 0]}
                            />
                            <Bar
                                dataKey="expense"
                                name="expense"
                                fill="var(--chart-2)"
                                radius={[4, 4, 0, 0]}
                            />
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            </CardContent>
        </Card>
    );
}
