import type { SankeyNode as D3SankeyNode } from 'd3-sankey';

import {
    SankeyChart,
    SankeyLink,
    SankeyNode,
    SankeyTooltip,
    type SankeyNodeDatum,
} from '@/components/charts/sankey';
import { TooltipContent, type TooltipRow } from '@/components/charts/tooltip/tooltip-content';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import type { TransactionSankeyFlow } from '@/types/transaction.types';

const ACCOUNT_NODE_COLOR = 'var(--chart-secondary)';
const UNCATEGORIZED_NODE_COLOR = 'var(--muted-foreground)';

type DrachmeSankeyNode = SankeyNodeDatum & {
    color?: string | null;
    kind?: 'account' | 'category';
    depth?: 1 | 2;
};

type TransactionsSankeyChartProps = {
    flow: TransactionSankeyFlow;
};

function resolveNodeColor(node: D3SankeyNode<DrachmeSankeyNode, { value: number }>): string {
    const datum = node as DrachmeSankeyNode & {
        color?: string | null;
        kind?: string;
    };

    if (datum.color) {
        return datum.color;
    }

    if (datum.kind === 'account') {
        return ACCOUNT_NODE_COLOR;
    }

    return UNCATEGORIZED_NODE_COLOR;
}

export function TransactionsSankeyChart({ flow }: TransactionsSankeyChartProps) {
    const { t } = useTranslation();

    if (flow.nodes.length === 0 || flow.links.length === 0) {
        return null;
    }

    const formatValue = (value: number) => formatCurrency(value, { precise: true });

    return (
        <section aria-label={t('transactions.sankey_title')}>
            <div className="rounded-xl border border-white/10 bg-black/10 p-2 md:p-4">
                <SankeyChart
                    data={flow}
                    aspectRatio="2 / 1"
                    maxHeight="50vh"
                    nodeWidth={16}
                    nodePadding={16}
                    margin={{ top: 16, right: 32, bottom: 16, left: 32 }}
                    className="w-full"
                >
                    <SankeyLink
                        getNodeColor={(node) =>
                            resolveNodeColor(node as D3SankeyNode<DrachmeSankeyNode, { value: number }>)
                        }
                    />
                    <SankeyNode
                        lineCap={4}
                        showLabels={false}
                        getNodeColor={(node) =>
                            resolveNodeColor(node as D3SankeyNode<DrachmeSankeyNode, { value: number }>)
                        }
                    />
                    <SankeyTooltip
                        formatValue={formatValue}
                        nodeContent={({ node }) => {
                            const rows: TooltipRow[] = [
                                {
                                    color: resolveNodeColor(node),
                                    label: t('transactions.amount'),
                                    value: formatValue(node.value ?? 0),
                                },
                            ];

                            return <TooltipContent rows={rows} title={node.name ?? ''} />;
                        }}
                        linkContent={({ link }) => {
                            const source = link.source as D3SankeyNode<
                                DrachmeSankeyNode,
                                { value: number }
                            >;
                            const target = link.target as D3SankeyNode<
                                DrachmeSankeyNode,
                                { value: number }
                            >;
                            const rows: TooltipRow[] = [
                                {
                                    color: resolveNodeColor(target),
                                    label: t('transactions.amount'),
                                    value: formatValue(link.value),
                                },
                            ];

                            return (
                                <TooltipContent
                                    rows={rows}
                                    title={`${source.name ?? ''} → ${target.name ?? ''}`}
                                />
                            );
                        }}
                    />
                </SankeyChart>
            </div>
        </section>
    );
}
