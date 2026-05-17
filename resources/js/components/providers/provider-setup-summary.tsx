import { useTranslation } from '@/hooks/use-translation';
import type {
    CsvOptions,
    ProviderFieldOption,
    ProviderRecord,
} from '@/types/provider.types';

type ProviderSetupSummaryProps = {
    provider: ProviderRecord;
    fieldOptions: ProviderFieldOption[];
};

function fieldLabel(
    field: string,
    fieldOptions: ProviderFieldOption[],
): string {
    return fieldOptions.find((option) => option.value === field)?.label ?? field;
}

function delimiterLabel(delimiter: string, t: (key: string) => string): string {
    if (delimiter === ';') {
        return t('providers.delimiter_semicolon');
    }

    if (delimiter === ',') {
        return t('providers.delimiter_comma');
    }

    return delimiter;
}

function formatCsvOptions(
    options: CsvOptions,
    t: (key: string) => string,
    includeDateFormat: boolean,
) {
    const rows = [
        { label: t('providers.delimiter'), value: delimiterLabel(options.delimiter, t) },
        { label: t('providers.encoding'), value: options.encoding },
        { label: t('providers.skip_rows'), value: String(options.skip_rows) },
    ];

    if (includeDateFormat) {
        rows.push({
            label: t('providers.date_format'),
            value: options.date_format,
        });
    }

    return rows;
}

export function ProviderSetupSummary({
    provider,
    fieldOptions,
}: ProviderSetupSummaryProps) {
    const { t } = useTranslation();
    const sortedColumns = [...provider.column_mapping.columns].sort(
        (a, b) => a.index - b.index,
    );
    const csvOptions = formatCsvOptions(
        provider.csv_options,
        t,
        provider.import_type !== 'positions',
    );

    return (
        <div className="space-y-6">
            <section className="space-y-3">
                <h2 className="text-sm font-semibold">{t('providers.setup_mapping')}</h2>
                <div className="overflow-x-auto rounded-lg border border-white/10">
                    <table className="w-full min-w-[320px] text-sm">
                        <thead className="text-muted-foreground border-b border-white/10 text-xs uppercase">
                            <tr>
                                <th className="px-3 py-2 text-left">
                                    {t('providers.column')}
                                </th>
                                <th className="px-3 py-2 text-left">
                                    {t('providers.setup_field')}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {sortedColumns.map((column) => (
                                <tr
                                    key={column.index}
                                    className="border-b border-white/5 last:border-0"
                                >
                                    <td className="text-muted-foreground px-3 py-2 font-mono text-xs">
                                        {column.index + 1}
                                    </td>
                                    <td className="px-3 py-2">
                                        {fieldLabel(column.field, fieldOptions)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>

            <section className="space-y-3">
                <h2 className="text-sm font-semibold">{t('providers.options_title')}</h2>
                <dl className="grid gap-3 sm:grid-cols-2">
                    {csvOptions.map((option) => (
                        <div
                            key={option.label}
                            className="rounded-lg border border-white/10 bg-white/5 px-3 py-2"
                        >
                            <dt className="text-muted-foreground text-xs uppercase tracking-wide">
                                {option.label}
                            </dt>
                            <dd className="mt-1 font-mono text-sm">{option.value}</dd>
                        </div>
                    ))}
                </dl>
            </section>
        </div>
    );
}
