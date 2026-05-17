import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import {
    columnLetter,
    importFieldCellClass,
    importFieldHeaderClass,
} from '@/lib/import-field-styles';
import { cn } from '@/lib/utils';
import type {
    ColumnMappingEntry,
    ColumnMappingField,
    ProviderFieldOption,
} from '@/types/provider.types';

type ProviderCsvMappingTableProps = {
    rows: string[][];
    columnMappings: ColumnMappingEntry[];
    /** CSV header labels (from skipped header row), indexed by column. */
    columnHeaders?: string[] | null;
    fieldOptions: ProviderFieldOption[];
    onMappingChange: (index: number, field: ColumnMappingField) => void;
    maxPreviewRows?: number;
};

function fieldLabel(
    field: ColumnMappingField,
    fieldOptions: ProviderFieldOption[],
): string {
    return fieldOptions.find((option) => option.value === field)?.label ?? field;
}

function columnHeaderLabel(
    index: number,
    columnHeaders: string[] | null | undefined,
    fallback: string,
): string {
    const header = columnHeaders?.[index]?.trim();

    return header && header.length > 0 ? header : fallback;
}

export function ProviderCsvMappingTable({
    rows,
    columnMappings,
    columnHeaders,
    fieldOptions,
    onMappingChange,
    maxPreviewRows = 8,
}: ProviderCsvMappingTableProps) {
    const { t } = useTranslation();
    const previewRows = rows.slice(0, maxPreviewRows);

    if (columnMappings.length === 0) {
        return null;
    }

    return (
        <div className="space-y-2">
            <div>
                <p className="text-sm font-medium">{t('providers.mapping_table_title')}</p>
                <p className="text-muted-foreground text-xs">
                    {t('providers.mapping_table_description')}
                </p>
            </div>
            <div className="overflow-x-auto rounded-md border">
                <table className="w-full min-w-[560px] border-collapse text-sm">
                    <thead>
                        <tr className="bg-muted/50 border-b">
                            <th className="text-muted-foreground w-10 px-2 py-2 text-left text-xs font-medium">
                                #
                            </th>
                            {columnMappings.map((column) => (
                                <th
                                    key={column.index}
                                    className="min-w-[140px] px-2 py-2 text-left align-top"
                                >
                                    <div className="space-y-2">
                                        <div className="space-y-0.5">
                                            <span className="block text-xs font-medium leading-tight">
                                                {columnHeaderLabel(
                                                    column.index,
                                                    columnHeaders,
                                                    `${t('providers.column')} ${columnLetter(column.index)}`,
                                                )}
                                            </span>
                                            {columnHeaders?.[column.index]?.trim() ? (
                                                <span className="text-muted-foreground text-[10px] uppercase tracking-wide">
                                                    {columnLetter(column.index)}
                                                </span>
                                            ) : null}
                                        </div>
                                        <Select
                                            value={column.field}
                                            onValueChange={(value) =>
                                                onMappingChange(
                                                    column.index,
                                                    value as ColumnMappingField,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="h-8 w-full text-xs">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {fieldOptions.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <Badge
                                            variant="outline"
                                            className={cn(
                                                'w-fit text-xs font-normal',
                                                importFieldHeaderClass(column.field),
                                            )}
                                        >
                                            {fieldLabel(column.field, fieldOptions)}
                                        </Badge>
                                    </div>
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {previewRows.map((row, rowIndex) => (
                            <tr
                                key={rowIndex}
                                className="border-border/50 border-b last:border-0"
                            >
                                <td className="text-muted-foreground px-2 py-2 text-xs tabular-nums">
                                    {rowIndex + 1}
                                </td>
                                {columnMappings.map((column) => (
                                    <td
                                        key={column.index}
                                        className={cn(
                                            'max-w-[200px] truncate px-2 py-2 font-mono text-xs',
                                            importFieldCellClass(column.field),
                                        )}
                                        title={row[column.index] ?? ''}
                                    >
                                        {row[column.index] ?? '—'}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            {rows.length > maxPreviewRows && (
                <p className="text-muted-foreground text-xs">
                    {t('providers.mapping_table_more_rows', {
                        count: rows.length - maxPreviewRows,
                    })}
                </p>
            )}
        </div>
    );
}
