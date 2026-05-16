const eurFormatter = new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

const eurFormatterPrecise = new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 2,
});

export function formatCurrency(
    value: number,
    options?: { precise?: boolean },
): string {
    return options?.precise
        ? eurFormatterPrecise.format(value)
        : eurFormatter.format(value);
}

export function formatPercent(value: number): string {
    const sign = value > 0 ? '+' : '';
    return `${sign}${value.toLocaleString('fr-FR', { maximumFractionDigits: 1 })} %`;
}
