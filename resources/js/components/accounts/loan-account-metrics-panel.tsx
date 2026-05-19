import { GlassPanel } from '@/components/glass-panel';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import type { LoanAccountMetrics } from '@/types/account.types';

type LoanAccountMetricsPanelProps = {
    metrics: LoanAccountMetrics;
    interestRate: number | null;
    endDate: string | null;
    paymentDay: number | null;
};

export function LoanAccountMetricsPanel({
    metrics,
    interestRate,
    endDate,
    paymentDay,
}: LoanAccountMetricsPanelProps) {
    const { t, locale } = useTranslation();

    const formatDate = (iso: string) =>
        new Intl.DateTimeFormat(locale === 'en' ? 'en-GB' : 'fr-FR', {
            dateStyle: 'medium',
        }).format(new Date(`${iso}T12:00:00`));

    const rows: { label: string; value: string }[] = [];

    if (metrics.monthly_payment !== null) {
        rows.push({
            label: t('accounts.loan_metrics.monthly_payment'),
            value: formatCurrency(metrics.monthly_payment, { precise: true }),
        });
    }

    rows.push(
        {
            label: t('accounts.loan_metrics.original_principal'),
            value: formatCurrency(metrics.original_principal, { precise: true }),
        },
        {
            label: t('accounts.loan_metrics.principal_repaid'),
            value: formatCurrency(metrics.principal_repaid, { precise: true }),
        },
        {
            label: t('accounts.loan_metrics.interest_paid_estimate'),
            value: formatCurrency(metrics.interest_paid_estimate, { precise: true }),
        },
    );

    if (interestRate !== null) {
        rows.push({
            label: t('accounts.loan_interest_rate'),
            value: t('accounts.loan_interest_rate_value', { rate: interestRate }),
        });
    }

    if (endDate !== null) {
        rows.push({
            label: t('accounts.loan_end_date'),
            value: formatDate(endDate),
        });
    }

    if (metrics.months_remaining !== null) {
        rows.push({
            label: t('accounts.loan_metrics.months_remaining'),
            value: String(metrics.months_remaining),
        });
    }

    if (metrics.estimated_remaining_interest !== null) {
        rows.push({
            label: t('accounts.loan_metrics.estimated_remaining_interest'),
            value: formatCurrency(metrics.estimated_remaining_interest, { precise: true }),
        });
    }

    if (metrics.estimated_total_cost !== null) {
        rows.push({
            label: t('accounts.loan_metrics.estimated_total_cost'),
            value: formatCurrency(metrics.estimated_total_cost, { precise: true }),
        });
    }

    return (
        <GlassPanel className="space-y-4 p-6">
            <div>
                <h2 className="text-lg font-semibold">{t('accounts.loan_metrics.title')}</h2>
                <p className="text-muted-foreground mt-1 text-sm">
                    {t('accounts.loan_metrics.hint')}
                </p>
                {paymentDay !== null ? (
                    <p className="text-muted-foreground mt-2 text-sm">
                        {t('accounts.payment_day_next', { day: paymentDay })}
                    </p>
                ) : null}
            </div>
            <dl className="grid gap-3 sm:grid-cols-2">
                {rows.map((row) => (
                    <div
                        key={row.label}
                        className="flex flex-col gap-0.5 rounded-md border border-border/50 px-3 py-2"
                    >
                        <dt className="text-muted-foreground text-xs">{row.label}</dt>
                        <dd className="font-medium tabular-nums">{row.value}</dd>
                    </div>
                ))}
            </dl>
        </GlassPanel>
    );
}
