export function formatNumber(value: number | null | undefined): string {
    return Number(value ?? 0).toLocaleString();
}
