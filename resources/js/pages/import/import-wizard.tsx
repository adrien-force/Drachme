import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, FileUp, Upload } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

import { EntityLogo } from '@/components/entity-logo';
import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
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
import { formatCurrency } from '@/lib/format-currency';
import { cn } from '@/lib/utils';
import type {
    ImportBatchPayload,
    ImportDuplicateAction,
    ImportPositionPreviewRow,
    ImportPreviewRow,
    ImportTransactionPreviewRow,
    ImportWizardPageProps,
} from '@/types/import.types';

function isTransactionPreviewRow(
    row: ImportPreviewRow,
): row is ImportTransactionPreviewRow {
    return 'date' in row || (row.status === 'error' && !('isin' in row));
}

function isPositionPreviewRow(row: ImportPreviewRow): row is ImportPositionPreviewRow {
    return 'isin' in row;
}

type Step = 'setup' | 'upload' | 'review' | 'done';

const PREVIEW_PAGE_SIZE = 50;

function resolveStep(status: ImportWizardPageProps['batch']): Step {
    if (status === null) {
        return 'setup';
    }

    if (status.status === 'completed') {
        return 'done';
    }

    if (status.status === 'preview') {
        return 'review';
    }

    return 'upload';
}

export default function ImportWizard({
    providers,
    accounts,
    batch,
}: ImportWizardPageProps) {
    const { t } = useTranslation();
    const step = resolveStep(batch);

    const setupForm = useForm({
        import_provider_id: batch?.import_provider_id
            ? String(batch.import_provider_id)
            : '',
        account_id: batch?.account_id ? String(batch.account_id) : '',
    });

    const [providerId, setProviderId] = useState(setupForm.data.import_provider_id);
    const [accountId, setAccountId] = useState(setupForm.data.account_id);
    const [csvFile, setCsvFile] = useState<File | null>(null);
    const [decisions, setDecisions] = useState<
        Record<number, ImportDuplicateAction>
    >({});

    useEffect(() => {
        if (batch?.status !== 'preview') {
            return;
        }

        const defaults: Record<number, ImportDuplicateAction> = {};
        for (const row of batch.preview_rows) {
            if (row.is_duplicate) {
                defaults[row.line] = batch.is_positions_import ? 'import' : 'skip';
            }
        }
        setDecisions(defaults);
    }, [batch?.id, batch?.status, batch?.preview_rows]);

    const selectedProvider = useMemo(
        () => providers.find((p) => String(p.id) === providerId),
        [providers, providerId],
    );

    const isPositionsProvider = selectedProvider?.import_type === 'positions';

    const eligibleAccounts = useMemo(() => {
        if (!isPositionsProvider) {
            return accounts;
        }

        return accounts.filter((account) => account.type === 'invest');
    }, [accounts, isPositionsProvider]);

    useEffect(() => {
        if (
            selectedProvider?.default_account_id &&
            accountId === '' &&
            step === 'setup'
        ) {
            const defaultAccount = accounts.find(
                (account) => account.id === selectedProvider.default_account_id,
            );

            if (
                defaultAccount &&
                (!isPositionsProvider || defaultAccount.type === 'invest')
            ) {
                setAccountId(String(selectedProvider.default_account_id));
            }
        }
    }, [selectedProvider, accountId, step, accounts, isPositionsProvider]);

    useEffect(() => {
        if (!isPositionsProvider || accountId === '') {
            return;
        }

        const account = accounts.find((item) => String(item.id) === accountId);

        if (account && account.type !== 'invest') {
            setAccountId('');
        }
    }, [isPositionsProvider, accountId, accounts]);

    const duplicateCount = useMemo(() => {
        if (!batch?.preview_rows) {
            return 0;
        }

        return batch.preview_rows.filter((row) => row.is_duplicate).length;
    }, [batch?.preview_rows]);

    const startImport = () => {
        setupForm.setData({
            import_provider_id: providerId,
            account_id: accountId,
        });
        setupForm.post('/import', {
            preserveScroll: true,
        });
    };

    const uploadCsv = () => {
        if (!batch || csvFile === null) {
            return;
        }

        const formData = new FormData();
        formData.append('file', csvFile);

        router.post(`/import/${batch.id}/parse`, formData, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const commitImport = () => {
        if (!batch) {
            return;
        }

        const payload = Object.entries(decisions).map(([line, action]) => ({
            line: Number(line),
            action,
        }));

        router.post(
            `/import/${batch.id}/commit`,
            { decisions: payload },
            { preserveScroll: true },
        );
    };

    const cancelImport = () => {
        if (!batch) {
            return;
        }

        router.delete(`/import/${batch.id}`);
    };

    const updateDecision = (line: number, action: ImportDuplicateAction) => {
        setDecisions((current) => ({ ...current, [line]: action }));
    };

    return (
        <>
            <Head title={t('import.title')} />

            <FadeIn className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {t('import.title')}
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        {t('import.subtitle')}
                    </p>
                </div>

                <StepIndicator step={step} />

                {providers.length === 0 && step === 'setup' ? (
                    <GlassPanel className="p-6 text-center">
                        <p className="text-muted-foreground text-sm">
                            {t('import.no_providers')}
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/providers/create">
                                {t('import.create_provider')}
                            </Link>
                        </Button>
                    </GlassPanel>
                ) : null}

                {step === 'setup' && providers.length > 0 ? (
                    <GlassPanel className="space-y-6 p-6">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label>{t('import.provider')}</Label>
                                <Select
                                    value={providerId}
                                    onValueChange={setProviderId}
                                >
                                    <SelectTrigger>
                                        <SelectValue
                                            placeholder={t(
                                                'import.choose_provider',
                                            )}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {providers.map((provider) => (
                                            <SelectItem
                                                key={provider.id}
                                                value={String(provider.id)}
                                            >
                                                <span className="flex items-center gap-2">
                                                    <EntityLogo
                                                        name={provider.name}
                                                        logoUrl={
                                                            provider.logo_url
                                                        }
                                                        className="size-5"
                                                    />
                                                    {provider.name}
                                                </span>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label>{t('import.account')}</Label>
                                <Select
                                    value={accountId}
                                    onValueChange={setAccountId}
                                >
                                    <SelectTrigger>
                                        <SelectValue
                                            placeholder={t(
                                                'import.choose_account',
                                            )}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {eligibleAccounts.map((account) => (
                                            <SelectItem
                                                key={account.id}
                                                value={String(account.id)}
                                            >
                                                <span className="flex items-center gap-2">
                                                    <EntityLogo
                                                        name={account.name}
                                                        logoUrl={
                                                            account.logo_url
                                                        }
                                                        className="size-5"
                                                    />
                                                    {account.name}
                                                </span>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <Button
                            disabled={
                                providerId === '' ||
                                accountId === '' ||
                                setupForm.processing
                            }
                            onClick={startImport}
                        >
                            {t('import.continue')}
                        </Button>
                    </GlassPanel>
                ) : null}

                {step === 'upload' && batch ? (
                    <GlassPanel className="space-y-6 p-6">
                        <p className="text-muted-foreground text-sm">
                            {batch.provider_name} → {batch.account_name}
                        </p>
                        <div className="space-y-2">
                            <Label htmlFor="csv-file">
                                {t('import.upload_label')}
                            </Label>
                            <Input
                                id="csv-file"
                                type="file"
                                accept=".csv,.txt"
                                onChange={(event) => {
                                    const file = event.target.files?.[0];
                                    setCsvFile(file ?? null);
                                }}
                            />
                            <p className="text-muted-foreground text-xs">
                                {t('import.upload_hint')}
                            </p>
                        </div>
                        <Button
                            disabled={csvFile === null}
                            onClick={uploadCsv}
                        >
                            <Upload className="mr-2 size-4" />
                            {t('import.analyze')}
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={cancelImport}
                        >
                            {t('import.cancel')}
                        </Button>
                    </GlassPanel>
                ) : null}

                {step === 'review' && batch ? (
                    <GlassPanel className="space-y-4 p-6">
                        {!batch.is_positions_import ? (
                            <AccountBalanceSummary batch={batch} variant="before" />
                        ) : null}
                        <div className="flex flex-wrap gap-3 text-sm">
                            {batch.error_count > 0 ? (
                                <span className="text-destructive">
                                    {t('import.errors_in_file', {
                                        count: batch.error_count,
                                    })}
                                </span>
                            ) : null}
                            {duplicateCount > 0 ? (
                                <span className="text-amber-500">
                                    {t('import.duplicates_found', {
                                        count: duplicateCount,
                                    })}
                                </span>
                            ) : null}
                        </div>

                        <PreviewTable
                            rows={batch.preview_rows}
                            decisions={decisions}
                            isPositionsImport={batch.is_positions_import}
                            showBalance={batch.maps_balance}
                            onDecisionChange={updateDecision}
                        />

                        <div className="flex flex-wrap gap-3">
                            <Button onClick={commitImport}>
                                <FileUp className="mr-2 size-4" />
                                {t('import.confirm_import')}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={cancelImport}
                            >
                                {t('import.cancel')}
                            </Button>
                        </div>
                    </GlassPanel>
                ) : null}

                {step === 'done' && batch ? (
                    <GlassPanel className="space-y-4 p-6">
                        {!batch.is_positions_import ? (
                            <AccountBalanceSummary batch={batch} variant="after" />
                        ) : null}
                        <ul className="text-muted-foreground space-y-2 text-sm">
                            <li>
                                {batch.is_positions_import
                                    ? t('import.summary_positions_imported', {
                                          count: batch.imported_count,
                                      })
                                    : t('import.summary_imported', {
                                          count: batch.imported_count,
                                      })}
                            </li>
                            <li>
                                {t('import.summary_skipped', {
                                    count: batch.skipped_count,
                                })}
                            </li>
                            <li>
                                {t('import.summary_replaced', {
                                    count: batch.replaced_count,
                                })}
                            </li>
                            {batch.error_count > 0 ? (
                                <li>
                                    {t('import.summary_errors', {
                                        count: batch.error_count,
                                    })}
                                </li>
                            ) : null}
                        </ul>
                        <div className="flex flex-wrap gap-3">
                            {batch.account_id ? (
                                <Button asChild variant="outline">
                                    <Link href={`/accounts/${batch.account_id}`}>
                                        {t('import.view_account')}
                                    </Link>
                                </Button>
                            ) : null}
                            <Button asChild>
                                <Link href="/import">{t('import.new_import')}</Link>
                            </Button>
                        </div>
                    </GlassPanel>
                ) : null}
            </FadeIn>
        </>
    );
}

function AccountBalanceSummary({
    batch,
    variant,
}: {
    batch: ImportBatchPayload;
    variant: 'before' | 'after';
}) {
    const { t } = useTranslation();

    if (batch.account_current_balance == null) {
        return null;
    }

    const balance = Number(batch.account_current_balance);
    const label =
        variant === 'after'
            ? t('import.summary_balance')
            : t('import.account_balance_before');

    return (
        <div className="rounded-lg border border-white/10 bg-white/5 p-4">
            <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">
                {label}
                {batch.account_name ? ` — ${batch.account_name}` : ''}
            </p>
            <p className="mt-2 text-2xl font-semibold tabular-nums">
                {formatCurrency(balance, { precise: true })}
            </p>
        </div>
    );
}

function StepIndicator({ step }: { step: Step }) {
    const { t } = useTranslation();
    const steps: { key: Step; label: string }[] = [
        { key: 'setup', label: t('import.step_setup') },
        { key: 'upload', label: t('import.step_upload') },
        { key: 'review', label: t('import.step_review') },
        { key: 'done', label: t('import.step_done') },
    ];
    const order: Step[] = ['setup', 'upload', 'review', 'done'];
    const currentIndex = order.indexOf(step);

    return (
        <ol className="flex flex-wrap gap-2 text-xs">
            {steps.map((item, index) => (
                <li
                    key={item.key}
                    className={cn(
                        'border-border/50 rounded-full border px-3 py-1',
                        index <= currentIndex
                            ? 'bg-primary/15 text-primary'
                            : 'text-muted-foreground',
                    )}
                >
                    {item.label}
                </li>
            ))}
        </ol>
    );
}

function PreviewTable({
    rows,
    decisions,
    isPositionsImport,
    showBalance,
    onDecisionChange,
}: {
    rows: ImportPreviewRow[];
    decisions: Record<number, ImportDuplicateAction>;
    isPositionsImport: boolean;
    showBalance: boolean;
    onDecisionChange: (line: number, action: ImportDuplicateAction) => void;
}) {
    const { t } = useTranslation();
    const [page, setPage] = useState(1);

    const totalRows = rows.length;
    const lastPage = Math.max(1, Math.ceil(totalRows / PREVIEW_PAGE_SIZE));

    useEffect(() => {
        setPage(1);
    }, [rows]);

    useEffect(() => {
        if (page > lastPage) {
            setPage(lastPage);
        }
    }, [page, lastPage]);

    const pageRows = useMemo(() => {
        const start = (page - 1) * PREVIEW_PAGE_SIZE;

        return rows.slice(start, start + PREVIEW_PAGE_SIZE);
    }, [rows, page]);

    const paginationFrom =
        totalRows === 0 ? 0 : (page - 1) * PREVIEW_PAGE_SIZE + 1;
    const paginationTo = Math.min(page * PREVIEW_PAGE_SIZE, totalRows);
    const showPagination = totalRows > PREVIEW_PAGE_SIZE;

    const paginationFooter = showPagination ? (
        <PreviewPaginationFooter
            from={paginationFrom}
            to={paginationTo}
            total={totalRows}
            page={page}
            lastPage={lastPage}
            onPageChange={setPage}
        />
    ) : null;

    if (isPositionsImport) {
        const errorColSpan = 6;

        return (
            <div className="space-y-3">
                {paginationFooter}
                <div className="overflow-x-auto rounded-lg border border-white/10">
                <table className="w-full min-w-[720px] text-left text-sm">
                    <thead className="text-muted-foreground border-b border-white/10 text-xs uppercase">
                        <tr>
                            <th className="px-3 py-2">{t('import.line')}</th>
                            <th className="px-3 py-2">{t('import.column_label')}</th>
                            <th className="px-3 py-2">{t('import.column_isin')}</th>
                            <th className="px-3 py-2 text-right">
                                {t('import.column_quantity')}
                            </th>
                            <th className="px-3 py-2 text-right">
                                {t('import.column_market_value')}
                            </th>
                            <th className="px-3 py-2">{t('import.duplicate')}</th>
                            <th className="px-3 py-2">
                                {t('import.duplicate_action')}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {pageRows.map((row) => (
                            <tr
                                key={row.line}
                                className={cn(
                                    'border-b border-white/5',
                                    row.status === 'error' && 'bg-destructive/10',
                                    row.is_duplicate && 'bg-amber-500/10',
                                )}
                            >
                                <td className="px-3 py-2 font-mono text-xs">
                                    {row.line}
                                </td>
                                {row.status === 'error' ? (
                                    <td
                                        colSpan={errorColSpan}
                                        className="text-destructive px-3 py-2 text-xs"
                                    >
                                        {row.error}
                                    </td>
                                ) : isPositionPreviewRow(row) ? (
                                    <>
                                        <td className="px-3 py-2">{row.label}</td>
                                        <td className="px-3 py-2 font-mono text-xs uppercase">
                                            {row.isin}
                                        </td>
                                        <td className="px-3 py-2 text-right tabular-nums">
                                            {row.quantity}
                                        </td>
                                        <td className="px-3 py-2 text-right font-mono">
                                            {formatCurrency(row.market_value ?? 0, {
                                                precise: true,
                                            })}
                                        </td>
                                        <td className="px-3 py-2">
                                            {row.is_duplicate ? '✓' : '—'}
                                        </td>
                                        <td className="px-3 py-2">
                                            {row.is_duplicate ? (
                                                <Select
                                                    value={
                                                        decisions[row.line] ?? 'skip'
                                                    }
                                                    onValueChange={(value) =>
                                                        onDecisionChange(
                                                            row.line,
                                                            value as ImportDuplicateAction,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger className="h-8">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="skip">
                                                            {t('import.action_skip')}
                                                        </SelectItem>
                                                        <SelectItem value="import">
                                                            {t('import.action_import')}
                                                        </SelectItem>
                                                        <SelectItem value="replace">
                                                            {t('import.action_replace')}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                    </>
                                ) : null}
                            </tr>
                        ))}
                    </tbody>
                </table>
                </div>
            </div>
        );
    }

    const errorColSpan = showBalance ? 6 : 5;

    return (
        <div className="space-y-3">
            {paginationFooter}
            <div className="overflow-x-auto rounded-lg border border-white/10">
            <table className="w-full min-w-[640px] text-left text-sm">
                <thead className="text-muted-foreground border-b border-white/10 text-xs uppercase">
                    <tr>
                        <th className="px-3 py-2">{t('import.line')}</th>
                        <th className="px-3 py-2">{t('import.column_date')}</th>
                        <th className="px-3 py-2">{t('import.column_label')}</th>
                        <th className="px-3 py-2 text-right">
                            {t('import.column_amount')}
                        </th>
                        {showBalance ? (
                            <th className="px-3 py-2 text-right">
                                {t('import.column_balance')}
                            </th>
                        ) : null}
                        <th className="px-3 py-2">{t('import.duplicate')}</th>
                        <th className="px-3 py-2">
                            {t('import.duplicate_action')}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {pageRows.map((row) => (
                        <tr
                            key={row.line}
                            className={cn(
                                'border-b border-white/5',
                                row.status === 'error' && 'bg-destructive/10',
                                row.is_duplicate && 'bg-amber-500/10',
                            )}
                        >
                            <td className="px-3 py-2 font-mono text-xs">
                                {row.line}
                            </td>
                            {row.status === 'error' ? (
                                <td
                                    colSpan={errorColSpan}
                                    className="text-destructive px-3 py-2 text-xs"
                                >
                                    {row.error}
                                </td>
                            ) : isTransactionPreviewRow(row) ? (
                                <>
                                    <td className="px-3 py-2">{row.date}</td>
                                    <td className="px-3 py-2">{row.label}</td>
                                    <td className="px-3 py-2 text-right font-mono">
                                        {formatCurrency(row.amount ?? 0, {
                                            precise: true,
                                        })}
                                    </td>
                                    {showBalance ? (
                                        <td className="px-3 py-2 text-right font-mono">
                                            {row.balance != null
                                                ? formatCurrency(row.balance, {
                                                      precise: true,
                                                  })
                                                : '—'}
                                        </td>
                                    ) : null}
                                    <td className="px-3 py-2">
                                        {row.is_duplicate ? '✓' : '—'}
                                    </td>
                                    <td className="px-3 py-2">
                                        {row.is_duplicate ? (
                                            <Select
                                                value={
                                                    decisions[row.line] ?? 'skip'
                                                }
                                                onValueChange={(value) =>
                                                    onDecisionChange(
                                                        row.line,
                                                        value as ImportDuplicateAction,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="h-8">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="skip">
                                                        {t('import.action_skip')}
                                                    </SelectItem>
                                                    <SelectItem value="import">
                                                        {t('import.action_import')}
                                                    </SelectItem>
                                                    <SelectItem value="replace">
                                                        {t('import.action_replace')}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        ) : (
                                            '—'
                                        )}
                                    </td>
                                </>
                            ) : null}
                        </tr>
                    ))}
                </tbody>
            </table>
            </div>
        </div>
    );
}

function PreviewPaginationFooter({
    from,
    to,
    total,
    page,
    lastPage,
    onPageChange,
}: {
    from: number;
    to: number;
    total: number;
    page: number;
    lastPage: number;
    onPageChange: (page: number) => void;
}) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-muted-foreground text-sm">
                {t('accounts.transactions_list.pagination_summary', {
                    from,
                    to,
                    total,
                })}
            </p>
            <div className="flex items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    disabled={page <= 1}
                    onClick={() => onPageChange(page - 1)}
                >
                    <ChevronLeft className="size-4" />
                    <span className="sr-only">
                        {t('accounts.transactions_list.previous')}
                    </span>
                </Button>
                <span className="text-muted-foreground text-sm tabular-nums">
                    {t('accounts.transactions_list.page_of', {
                        page,
                        last: lastPage,
                    })}
                </span>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    disabled={page >= lastPage}
                    onClick={() => onPageChange(page + 1)}
                >
                    <ChevronRight className="size-4" />
                    <span className="sr-only">
                        {t('accounts.transactions_list.next')}
                    </span>
                </Button>
            </div>
        </div>
    );
}
