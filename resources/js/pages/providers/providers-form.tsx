import { Head, Link, router } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { EntityLogo } from '@/components/entity-logo';
import { DateFormatConfirmPanel } from '@/components/providers/date-format-confirm-panel';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { readCsrfToken } from '@/lib/csrf';
import { detectDateFormat } from '@/lib/detect-date-format';
import { formatCurrency } from '@/lib/format-currency';
import {
    buildColumnMappings,
    extractDateSamples,
    getDateColumnIndex,
    isImportMappingValid,
    mappingPayload,
} from '@/lib/import-mapping';
import { parseCsvSample } from '@/lib/parse-csv-sample';
import { cn } from '@/lib/utils';
import type {
    ColumnMappingEntry,
    CsvOptions,
    DateFormatSuggestion,
    ImportColumnField,
    NormalizedPreviewRow,
    ProvidersFormPageProps,
} from '@/types/provider.types';

const NONE_ACCOUNT = 'none';

export default function ProvidersForm({
    provider,
    accounts,
    fieldOptions,
    defaultCsvOptions,
}: ProvidersFormPageProps) {
    const { t } = useTranslation();
    const isEditing = provider !== null;

    const [name, setName] = useState(provider?.name ?? '');
    const [defaultAccountId, setDefaultAccountId] = useState<string>(
        provider?.default_account_id ? String(provider.default_account_id) : NONE_ACCOUNT,
    );
    const [csvOptions, setCsvOptions] = useState<CsvOptions>(
        provider?.csv_options ?? defaultCsvOptions,
    );
    const [columnMappings, setColumnMappings] = useState<ColumnMappingEntry[]>(
        provider?.column_mapping.columns ?? [],
    );
    const [sampleText, setSampleText] = useState('');
    const [previewRows, setPreviewRows] = useState<NormalizedPreviewRow[]>([]);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [dateSuggestion, setDateSuggestion] = useState<DateFormatSuggestion | null>(
        null,
    );
    const [dateFormatConfirmed, setDateFormatConfirmed] = useState(isEditing);
    const [dateFormatManual, setDateFormatManual] = useState(isEditing);

    const rawRows = useMemo(
        () =>
            parseCsvSample(sampleText, {
                delimiter: csvOptions.delimiter,
                enclosure: csvOptions.enclosure,
                skipRows: csvOptions.skip_rows,
            }),
        [sampleText, csvOptions.delimiter, csvOptions.enclosure, csvOptions.skip_rows],
    );

    const columnCount = rawRows[0]?.length ?? 0;

    useEffect(() => {
        setColumnMappings((current) => buildColumnMappings(columnCount, current));
    }, [columnCount]);

    const dateColumnIndex = getDateColumnIndex(columnMappings);
    const hasDateSamples =
        dateColumnIndex !== null && extractDateSamples(rawRows, dateColumnIndex).length > 0;

    const mappingValid = isImportMappingValid(columnMappings);
    const previewReady =
        mappingValid &&
        rawRows.length > 0 &&
        previewRows.length > 0 &&
        previewRows.every((row) => !('error' in row));

    const needsDateFormatConfirm =
        hasDateSamples &&
        dateSuggestion !== null &&
        dateSuggestion.format !== csvOptions.date_format &&
        !dateFormatConfirmed;

    const hasAmountSignedColumn = columnMappings.some(
        (column) => column.field === 'amount_signed',
    );
    const mapsBalance = columnMappings.some((column) => column.field === 'balance');

    const hasSample = rawRows.length > 0;

    const canSave =
        name.trim().length > 0 &&
        mappingValid &&
        !needsDateFormatConfirm &&
        (isEditing && !hasSample ? true : previewReady);

    const selectedAccount = accounts.find(
        (account) => String(account.id) === defaultAccountId,
    );
    const displayLogoUrl = selectedAccount?.logo_url ?? null;
    const displayLogoName = selectedAccount?.name ?? name;

    const applyDetectedDateFormat = useCallback(
        (columnIndex: number, autoApply: boolean) => {
            const samples = extractDateSamples(rawRows, columnIndex);

            if (samples.length === 0) {
                setDateSuggestion(null);
                return;
            }

            const suggestion = detectDateFormat(samples);
            setDateSuggestion(suggestion);

            if (suggestion === null) {
                setDateFormatConfirmed(false);

                return;
            }

            if (autoApply && !dateFormatManual) {
                setCsvOptions((current) => ({
                    ...current,
                    date_format: suggestion.format,
                }));
                setDateFormatConfirmed(true);

                return;
            }

            setDateFormatConfirmed(
                suggestion.format === csvOptions.date_format || dateFormatManual,
            );
        },
        [rawRows, dateFormatManual, csvOptions.date_format],
    );

    useEffect(() => {
        if (dateColumnIndex === null || rawRows.length === 0) {
            setDateSuggestion(null);
            return;
        }

        const timer = window.setTimeout(() => {
            applyDetectedDateFormat(dateColumnIndex, !dateFormatManual);
        }, 200);

        return () => window.clearTimeout(timer);
    }, [dateColumnIndex, rawRows, dateFormatManual, applyDetectedDateFormat]);

    useEffect(() => {
        if (!mappingValid || rawRows.length === 0) {
            setPreviewRows([]);
            return;
        }

        const timer = window.setTimeout(async () => {
            setPreviewLoading(true);

            try {
                const response = await fetch('/providers/preview', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': readCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        sample_rows: rawRows.slice(0, 10),
                        column_mapping: mappingPayload(columnMappings),
                        csv_options: csvOptions,
                    }),
                });

                if (!response.ok) {
                    setPreviewRows([]);
                    return;
                }

                const data = (await response.json()) as { rows: NormalizedPreviewRow[] };
                setPreviewRows(data.rows);
            } catch {
                setPreviewRows([]);
            } finally {
                setPreviewLoading(false);
            }
        }, 400);

        return () => window.clearTimeout(timer);
    }, [columnMappings, csvOptions, rawRows, mappingValid]);

    const updateMappingField = (index: number, field: ImportColumnField) => {
        const nextMappings = columnMappings.map((column) =>
            column.index === index ? { ...column, field } : column,
        );

        setColumnMappings(nextMappings);

        if (field === 'date') {
            queueMicrotask(() => applyDetectedDateFormat(index, true));
            return;
        }

        if (!nextMappings.some((column) => column.field === 'date')) {
            setDateSuggestion(null);
        }
    };

    const handleSampleFileChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file) {
            return;
        }

        const text = await file.text();
        setSampleText(text);
        event.target.value = '';
    };

    const handleSubmit = () => {
        if (!canSave) {
            return;
        }

        setSubmitting(true);

        const payload = {
            name: name.trim(),
            default_account_id:
                defaultAccountId === NONE_ACCOUNT ? null : Number(defaultAccountId),
            column_mapping: mappingPayload(columnMappings),
            csv_options: csvOptions,
        };

        const options = {
            preserveScroll: true,
            onFinish: () => setSubmitting(false),
        };

        if (isEditing && provider) {
            router.put(`/providers/${provider.id}`, payload, options);

            return;
        }

        router.post('/providers', payload, options);
    };

    return (
        <>
            <Head title={isEditing ? t('providers.edit_title') : t('providers.create_title')} />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {isEditing ? t('providers.edit_title') : t('providers.create_title')}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {isEditing
                            ? t('providers.edit_description')
                            : t('providers.create_description')}
                    </p>
                </div>

                <FadeIn>
                    <GlassPanel className="space-y-4 p-6">
                        <div className="grid gap-2 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="provider-name">{t('providers.name')}</Label>
                                <Input
                                    id="provider-name"
                                    value={name}
                                    onChange={(event) => setName(event.target.value)}
                                    placeholder={t('providers.name_placeholder')}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="default-account">
                                    {t('providers.default_account')}
                                </Label>
                                <Select
                                    value={defaultAccountId}
                                    onValueChange={setDefaultAccountId}
                                >
                                    <SelectTrigger id="default-account" className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={NONE_ACCOUNT}>
                                            {t('providers.default_account_none')}
                                        </SelectItem>
                                        {accounts.map((account) => (
                                            <SelectItem
                                                key={account.id}
                                                value={String(account.id)}
                                            >
                                                {account.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                            <EntityLogo
                                name={displayLogoName || '?'}
                                logoUrl={displayLogoUrl}
                                className="size-14 shrink-0"
                            />
                            <p className="text-muted-foreground text-sm">
                                {defaultAccountId === NONE_ACCOUNT
                                    ? t('providers.logo_from_account_none')
                                    : t('providers.logo_from_account', {
                                          account: selectedAccount?.name ?? '',
                                      })}
                            </p>
                        </div>
                    </GlassPanel>
                </FadeIn>

                <FadeIn delay={0.05}>
                    <GlassPanel className="space-y-4 p-6">
                        <div>
                            <h2 className="font-semibold">{t('providers.sample_title')}</h2>
                            <p className="text-muted-foreground text-sm">
                                {t('providers.sample_description')}
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-3">
                            <Button type="button" variant="outline" asChild>
                                <label className="cursor-pointer">
                                    <Upload className="mr-2 size-4" />
                                    {t('providers.upload_file')}
                                    <input
                                        type="file"
                                        accept=".csv,.txt,text/csv"
                                        className="sr-only"
                                        onChange={handleSampleFileChange}
                                    />
                                </label>
                            </Button>
                        </div>
                        <textarea
                            value={sampleText}
                            onChange={(event) => setSampleText(event.target.value)}
                            placeholder={t('providers.sample_placeholder')}
                            rows={6}
                            className={cn(
                                'border-input placeholder:text-muted-foreground w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none',
                                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                            )}
                        />
                        {rawRows.length > 0 && (
                            <div className="overflow-x-auto">
                                <p className="text-muted-foreground mb-2 text-xs font-medium uppercase">
                                    {t('providers.raw_preview')}
                                </p>
                                <table className="w-full min-w-[480px] text-sm">
                                    <tbody>
                                        {rawRows.slice(0, 5).map((row, rowIndex) => (
                                            <tr
                                                key={rowIndex}
                                                className="border-border/40 border-b last:border-0"
                                            >
                                                {row.map((cell, cellIndex) => (
                                                    <td
                                                        key={cellIndex}
                                                        className="px-3 py-2"
                                                    >
                                                        {cell}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </GlassPanel>
                </FadeIn>

                {columnCount > 0 && (
                    <FadeIn delay={0.1}>
                        <GlassPanel className="space-y-4 p-6">
                            <div>
                                <h2 className="font-semibold">{t('providers.mapping_title')}</h2>
                                <p className="text-muted-foreground text-sm">
                                    {t('providers.mapping_description')}
                                </p>
                            </div>
                            <div className="grid gap-3">
                                {columnMappings.map((column) => (
                                    <div
                                        key={column.index}
                                        className="grid gap-2 sm:grid-cols-[120px_1fr]"
                                    >
                                        <Label>
                                            {t('providers.column')} {column.index + 1}
                                        </Label>
                                        <Select
                                            value={column.field}
                                            onValueChange={(value) =>
                                                updateMappingField(
                                                    column.index,
                                                    value as ImportColumnField,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
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
                                    </div>
                                ))}
                            </div>
                            {!mappingValid && (
                                <p className="text-destructive text-sm">
                                    {t('providers.mapping_incomplete')}
                                </p>
                            )}
                            {hasAmountSignedColumn && (
                                <p className="text-muted-foreground text-xs">
                                    {t('providers.amount_signed_hint')}
                                </p>
                            )}
                        </GlassPanel>
                    </FadeIn>
                )}

                <FadeIn delay={0.15}>
                    <GlassPanel className="grid gap-4 p-6 sm:grid-cols-2">
                        <h2 className="font-semibold sm:col-span-2">
                            {t('providers.options_title')}
                        </h2>
                        <div className="grid gap-2">
                            <Label>{t('providers.delimiter')}</Label>
                            <Select
                                value={csvOptions.delimiter}
                                onValueChange={(value) =>
                                    setCsvOptions((current) => ({
                                        ...current,
                                        delimiter: value,
                                    }))
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value=";">
                                        {t('providers.delimiter_semicolon')}
                                    </SelectItem>
                                    <SelectItem value=",">
                                        {t('providers.delimiter_comma')}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label>{t('providers.encoding')}</Label>
                            <Select
                                value={csvOptions.encoding}
                                onValueChange={(value) =>
                                    setCsvOptions((current) => ({
                                        ...current,
                                        encoding: value,
                                    }))
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="UTF-8">UTF-8</SelectItem>
                                    <SelectItem value="ISO-8859-1">ISO-8859-1</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="skip-rows">{t('providers.skip_rows')}</Label>
                            <Input
                                id="skip-rows"
                                type="number"
                                min={0}
                                value={csvOptions.skip_rows}
                                onChange={(event) =>
                                    setCsvOptions((current) => ({
                                        ...current,
                                        skip_rows: Number(event.target.value),
                                    }))
                                }
                            />
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="date-format">{t('providers.date_format')}</Label>
                            <Input
                                id="date-format"
                                value={csvOptions.date_format}
                                onChange={(event) => {
                                    const value = event.target.value;
                                    setDateFormatManual(true);
                                    setCsvOptions((current) => ({
                                        ...current,
                                        date_format: value,
                                    }));
                                    setDateFormatConfirmed(true);
                                }}
                            />
                            <p className="text-muted-foreground text-xs">
                                {t('providers.date_format_hint')}
                            </p>
                            {dateSuggestion && (
                                <DateFormatConfirmPanel
                                    suggestion={dateSuggestion}
                                    currentFormat={csvOptions.date_format}
                                    confirmed={dateFormatConfirmed}
                                    onConfirm={(format) => {
                                        setCsvOptions((current) => ({
                                            ...current,
                                            date_format: format,
                                        }));
                                        setDateFormatManual(false);
                                        setDateFormatConfirmed(true);
                                    }}
                                    onDismissManual={() => {
                                        setDateFormatManual(true);
                                        setDateFormatConfirmed(true);
                                    }}
                                />
                            )}
                            {hasDateSamples && dateSuggestion === null && (
                                <p className="text-muted-foreground text-xs">
                                    {t('providers.date_format_not_detected')}
                                </p>
                            )}
                        </div>
                    </GlassPanel>
                </FadeIn>

                <FadeIn delay={0.2}>
                    <GlassPanel className="space-y-4 p-6">
                        <h2 className="font-semibold">{t('providers.normalized_preview')}</h2>
                        {previewLoading && (
                            <p className="text-muted-foreground text-sm">
                                {t('providers.preview_loading')}
                            </p>
                        )}
                        {!previewLoading && previewRows.length === 0 && (
                            <p className="text-muted-foreground text-sm">
                                {t('providers.preview_empty')}
                            </p>
                        )}
                        {previewRows.length > 0 && (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-muted-foreground border-b text-left text-xs uppercase">
                                        <th className="w-12 py-2 pr-2">#</th>
                                        <th className="py-2 pr-4">
                                            {t('providers.fields.date')}
                                        </th>
                                        <th className="py-2 pr-4">
                                            {t('providers.fields.label')}
                                        </th>
                                        <th className="py-2 text-right">
                                            {t('providers.fields.amount_signed')}
                                        </th>
                                        {mapsBalance ? (
                                            <th className="py-2 text-right">
                                                {t('providers.fields.balance')}
                                            </th>
                                        ) : null}
                                    </tr>
                                </thead>
                                <tbody>
                                    {previewRows.map((row, index) => (
                                        <tr key={index} className="border-border/40 border-b">
                                            <td className="text-muted-foreground py-2 pr-2 tabular-nums">
                                                {row.line}
                                            </td>
                                            {'error' in row ? (
                                                <td
                                                    colSpan={mapsBalance ? 4 : 3}
                                                    className="text-destructive py-2 text-sm leading-relaxed"
                                                >
                                                    {row.error}
                                                </td>
                                            ) : (
                                                <>
                                                    <td className="py-2 pr-4">{row.date}</td>
                                                    <td className="py-2 pr-4">{row.label}</td>
                                                    <td className="py-2 text-right tabular-nums">
                                                        {formatCurrency(row.amount, {
                                                            precise: true,
                                                        })}
                                                    </td>
                                                    {mapsBalance ? (
                                                        <td className="py-2 text-right tabular-nums">
                                                            {row.balance != null
                                                                ? formatCurrency(
                                                                      row.balance,
                                                                      {
                                                                          precise: true,
                                                                      },
                                                                  )
                                                                : '—'}
                                                        </td>
                                                    ) : null}
                                                </>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </GlassPanel>
                </FadeIn>

                <div className="flex flex-wrap gap-3">
                    <Button type="button" disabled={!canSave || submitting} onClick={handleSubmit}>
                        {t('providers.save')}
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <Link href="/providers">{t('providers.cancel')}</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}

ProvidersForm.layout = {
    breadcrumbs: [
        { title: 'Fournisseurs', href: '/providers' },
    ],
};
