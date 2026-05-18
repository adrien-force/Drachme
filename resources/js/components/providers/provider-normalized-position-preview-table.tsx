import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import { cn } from '@/lib/utils';
import type { NormalizedPositionPreviewRow } from '@/types/provider.types';

type ProviderNormalizedPositionPreviewTableProps = {
    rows: NormalizedPositionPreviewRow[];
};

export function ProviderNormalizedPositionPreviewTable({
    rows,
}: ProviderNormalizedPositionPreviewTableProps) {
    const { t } = useTranslation();

    if (rows.length === 0) {
        return null;
    }

    return (
        <div className="overflow-x-auto rounded-md border">
            <table className="w-full min-w-[560px] border-collapse text-sm">
                <thead>
                    <tr className="bg-muted/50 border-b text-left">
                        <th className="text-muted-foreground w-12 px-3 py-2 text-xs font-medium uppercase">
                            #
                        </th>
                        <th className="px-3 py-2 text-xs font-medium uppercase">
                            {t('providers.position_fields.position_label')}
                        </th>
                        <th className="px-3 py-2 text-xs font-medium uppercase">
                            {t('providers.position_fields.isin')}
                        </th>
                        <th className="px-3 py-2 text-xs font-medium uppercase">
                            {t('providers.position_fields.market_symbol')}
                        </th>
                        <th className="px-3 py-2 text-right text-xs font-medium uppercase">
                            {t('providers.position_fields.quantity')}
                        </th>
                        <th className="px-3 py-2 text-right text-xs font-medium uppercase">
                            {t('providers.position_fields.average_price')}
                        </th>
                        <th className="px-3 py-2 text-right text-xs font-medium uppercase">
                            {t('import.column_market_value')}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row, index) => (
                        <tr
                            key={index}
                            className={cn(
                                'border-border/50 border-b last:border-0',
                                'error' in row && 'bg-destructive/5',
                            )}
                        >
                            <td className="text-muted-foreground px-3 py-2 tabular-nums">
                                {row.line}
                            </td>
                            {'error' in row ? (
                                <td
                                    colSpan={6}
                                    className="text-destructive px-3 py-2 text-sm"
                                >
                                    {row.error}
                                </td>
                            ) : (
                                <>
                                    <td
                                        className="max-w-[200px] truncate px-3 py-2"
                                        title={row.label}
                                    >
                                        {row.label}
                                    </td>
                                    <td className="px-3 py-2 font-mono text-xs uppercase">
                                        {row.isin}
                                    </td>
                                    <td className="px-3 py-2 font-mono text-xs uppercase">
                                        {row.market_symbol ?? '—'}
                                    </td>
                                    <td className="px-3 py-2 text-right tabular-nums">
                                        {row.quantity}
                                    </td>
                                    <td className="px-3 py-2 text-right tabular-nums">
                                        {formatCurrency(row.average_price, { precise: true })}
                                    </td>
                                    <td className="px-3 py-2 text-right tabular-nums">
                                        {formatCurrency(row.market_value, { precise: true })}
                                    </td>
                                </>
                            )}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
