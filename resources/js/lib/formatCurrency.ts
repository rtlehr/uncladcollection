export function formatCurrency(
    priceCents: number,
    currency = 'USD',
    locale?: string,
): string {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currency.toUpperCase(),
    }).format(priceCents / 100);
}
