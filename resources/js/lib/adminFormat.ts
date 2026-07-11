export function formatAdminDate(
    value: string | null | undefined,
    fallback = '—',
): string {
    if (!value) {
        return fallback;
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? fallback
        : date.toLocaleString();
}

export function formatAdminNumber(
    value: number | null | undefined,
): string {
    return Number(value ?? 0).toLocaleString();
}

export function formatAdminCurrency(
    cents: number | null | undefined,
    currency = 'USD',
): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
    }).format(Number(cents ?? 0) / 100);
}

export function humanizeAdminValue(value: string): string {
    return value
        .replaceAll('_', ' ')
        .replaceAll('-', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}
