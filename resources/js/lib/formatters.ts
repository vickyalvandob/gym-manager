export function formatCurrency(value: string, currency: string): string {
    const fractionDigits = value.endsWith('.00') ? 0 : 2;

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency,
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
    }).format(Number(value));
}

export function formatDate(
    value: string | null,
    timeZone = 'Asia/Jakarta',
): string {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone,
    }).format(new Date(value));
}

export function formatDateTime(
    value: string | null,
    timeZone = 'Asia/Jakarta',
): string {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone,
    }).format(new Date(value));
}
