import { Head, Link, router } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';

import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { EntityLogo } from '@/components/entity-logo';
import { CsvSampleDropzone } from '@/components/providers/csv-sample-dropzone';
import { DateFormatConfirmPanel } from '@/components/providers/date-format-confirm-panel';
import { ProviderCsvMappingTable } from '@/components/providers/provider-csv-mapping-table';
import { ProviderNormalizedPositionPreviewTable } from '@/components/providers/provider-normalized-position-preview-table';
import { ProviderNormalizedPreviewTable } from '@/components/providers/provider-normalized-preview-table';
import { Button } from '@/components/ui/button';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
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
import {
    extractDateSamples,
    getDateColumnIndex,
    mappingPayload,
} from '@/lib/import-mapping';
import {
    buildColumnMappingsForType,
    isMappingValidForType,
    rowLooksLikePositionHeader,
} from '@/lib/import-position-mapping';
import { parseCsvHeaderRow, parseCsvSample } from '@/lib/parse-csv-sample';
import type {
    ColumnMappingEntry,
    ColumnMappingField,
    CsvOptions,
    DateFormatSuggestion,
    ImportProviderType,
    NormalizedPositionPreviewRow,
    NormalizedPreviewRow,
    ProvidersFormPageProps,
} from '@/types/provider.types';

const NONE_ACCOUNT = 'none';

export default function ProvidersForm({
    provider,
    accounts,
    fieldOptions,
    positionFieldOptions,
    defaultCsvOptions,
}: ProvidersFormPageProps) {
    const { t } = useTranslation();
    const isEditing = provider !== null;

    const [importType, setImportType] = useState<ImportProviderType>(
        provider?.import_type ?? 'transactions',
    );
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
    const [sampleFileName, setSampleFileName] = useState<string | null>(null);
    const [transactionPreviewRows, setTransactionPreviewRows] = useState<
        NormalizedPreviewRow[]
    >([]);
    const [positionPreviewRows, setPositionPreviewRows] = useState<
        NormalizedPositionPreviewRow[]
    >([]);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [dateSuggestion, setDateSuggestion] = useState<DateFormatSuggestion | null>(
        null,
    );
    const [dateFormatConfirmed, setDateFormatConfirmed] = useState(isEditing);
    const [dateFormatManual, setDateFormatManual] = useState(isEditing);

    const csvParseOptions = useMemo(
        () => ({
            delimiter: csvOptions.delimiter,
            enclosure: csvOptions.enclosure,
            skipRows: csvOptions.skip_rows,
        }),
        [csvOptions.delimiter, csvOptions.enclosure, csvOptions.skip_rows],
    );

    const headerCells = useMemo(
        () => parseCsvHeaderRow(sampleText, csvParseOptions),
        [sampleText, csvParseOptions],
    );

    const rawRows = useMemo(
        () => parseCsvSample(sampleText, csvParseOptions),
        [sampleText, csvParseOptions],
    );

    const columnCount = rawRows[0]?.length ?? headerCells?.length ?? 0;
    const isPositionsImport = importType === 'positions';
    const activeFieldOptions = isPositionsImport ? positionFieldOptions : fieldOptions;

    useEffect(() => {
        setColumnMappings((current) =>
            buildColumnMappingsForType(
                columnCount,
                current,
                importType,
                headerCells ?? undefined,
            ),
        );
    }, [columnCount, importType, headerCells]);

    const dateColumnIndex = isPositionsImport ? null : getDateColumnIndex(columnMappings);
    const hasDateSamples =
        dateColumnIndex !== null && extractDateSamples(rawRows, dateColumnIndex).length > 0;

    const mappingValid = isMappingValidForType(columnMappings, importType);
    const previewRows = isPositionsImport ? positionPreviewRows : transactionPreviewRows;
    const previewReady =
        mappingValid &&
        rawRows.length > 0 &&
        previewRows.length > 0 &&
        previewRows.every((row) => !('error' in row));

    const needsDateFormatConfirm =
        !isPositionsImport &&
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
            setTransactionPreviewRows([]);
            setPositionPreviewRows([]);
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
                        import_type: importType,
                        sample_rows: rawRows.slice(0, 10),
                        column_mapping: mappingPayload(columnMappings),
                        csv_options: csvOptions,
                    }),
                });

                if (!response.ok) {
                    setTransactionPreviewRows([]);
                    setPositionPreviewRows([]);
                    return;
                }

                const data = (await response.json()) as {
                    rows: NormalizedPreviewRow[] | NormalizedPositionPreviewRow[];
                    import_type: ImportProviderType;
                };

                if (data.import_type === 'positions') {
                    setPositionPreviewRows(
                        data.rows as NormalizedPositionPreviewRow[],
                    );
                    setTransactionPreviewRows([]);
                } else {
                    setTransactionPreviewRows(data.rows as NormalizedPreviewRow[]);
                    setPositionPreviewRows([]);
                }
            } catch {
                setTransactionPreviewRows([]);
                setPositionPreviewRows([]);
            } finally {
                setPreviewLoading(false);
            }
        }, 400);

        return () => window.clearTimeout(timer);
    }, [columnMappings, csvOptions, rawRows, mappingValid, importType]);

    const handleImportTypeChange = (nextType: ImportProviderType) => {
        setImportType(nextType);
        setTransactionPreviewRows([]);
        setPositionPreviewRows([]);
        setColumnMappings((current) =>
            buildColumnMappingsForType(
                columnCount,
                current,
                nextType,
                headerCells ?? undefined,
            ),
        );
    };

    const handleSampleChange = (text: string) => {
        setSampleText(text);
        if (!isEditing && text.trim() !== '') {
            const headerRow = parseCsvHeaderRow(text, {
                delimiter: csvOptions.delimiter,
                enclosure: csvOptions.enclosure,
                skipRows: csvOptions.skip_rows,
            });
            if (headerRow && rowLooksLikePositionHeader(headerRow)) {
                setImportType('positions');
            }
        }
    };

    const updateMappingField = (index: number, field: ColumnMappingField) => {
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

    const handleSubmit = () => {
        if (!canSave) {
            return;
        }

        setSubmitting(true);

        const payload = {
            name: name.trim(),
            import_type: importType,
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
                        <div className="grid gap-2">
                            <Label>{t('providers.import_type_label')}</Label>
                            <ToggleGroup
                                type="single"
                                variant="outline"
                                value={importType}
                                onValueChange={(value) => {
                                    if (value === 'transactions' || value === 'positions') {
                                        handleImportTypeChange(value);
                                    }
                                }}
                                className="flex w-full flex-wrap justify-start"
                            >
                                <ToggleGroupItem value="transactions" className="flex-1 sm:flex-none">
                                    {t('providers.import_type_transactions')}
                                </ToggleGroupItem>
                                <ToggleGroupItem value="positions" className="flex-1 sm:flex-none">
                                    {t('providers.import_type_positions')}
                                </ToggleGroupItem>
                            </ToggleGroup>
                            {isPositionsImport ? (
                                <p className="text-muted-foreground text-xs">
                                    {t('providers.import_type_positions_hint')}
                                </p>
                            ) : null}
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
                        <CsvSampleDropzone
                            value={sampleText}
                            onChange={handleSampleChange}
                            fileName={sampleFileName}
                            onFileNameChange={setSampleFileName}
                            disabled={submitting}
                        />
                        {columnCount > 0 && (
                            <ProviderCsvMappingTable
                                rows={rawRows}
                                columnMappings={columnMappings}
                                columnHeaders={headerCells}
                                fieldOptions={activeFieldOptions}
                                onMappingChange={updateMappingField}
                            />
                        )}
                        {columnCount > 0 && !mappingValid && (
                            <p className="text-destructive text-sm">
                                {t('providers.mapping_incomplete')}
                            </p>
                        )}
                        {columnCount > 0 && !isPositionsImport && hasAmountSignedColumn && (
                            <p className="text-muted-foreground text-xs">
                                {t('providers.amount_signed_hint')}
                            </p>
                        )}
                    </GlassPanel>
                </FadeIn>

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
                        {!isPositionsImport ? (
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
                        ) : null}
                    </GlassPanel>
                </FadeIn>

                <FadeIn delay={0.2}>
                    <GlassPanel className="space-y-4 p-6">
                        <div>
                            <h2 className="font-semibold">
                                {t('providers.normalized_preview')}
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                {t('providers.normalized_preview_description')}
                            </p>
                        </div>
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
                        {!previewLoading && previewRows.length > 0 && isPositionsImport ? (
                            <ProviderNormalizedPositionPreviewTable
                                rows={positionPreviewRows}
                            />
                        ) : null}
                        {!previewLoading && previewRows.length > 0 && !isPositionsImport ? (
                            <ProviderNormalizedPreviewTable
                                rows={transactionPreviewRows}
                                mapsBalance={mapsBalance}
                            />
                        ) : null}
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
