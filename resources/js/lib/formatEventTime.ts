export function formatEventTime(
    timestamp: number | null | undefined,
    timezone: string,
    options: Intl.DateTimeFormatOptions = {},
): string {
    if (timestamp == null) {
        return '—';
    }

    return new Intl.DateTimeFormat(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        timeZone: timezone,
        ...options,
    }).format(new Date(timestamp * 1000));
}

export function formatEventTimeDual(
    timestamp: number | null | undefined,
    eventTimezone: string,
): { local: string; eventLocal: string } {
    if (timestamp == null) {
        return { local: '—', eventLocal: '—' };
    }

    return {
        local: formatEventTime(timestamp, Intl.DateTimeFormat().resolvedOptions().timeZone),
        eventLocal: formatEventTime(timestamp, eventTimezone),
    };
}

export function monthGroupKey(timestamp: number | null | undefined): string {
    if (timestamp == null) {
        return 'unknown';
    }

    const date = new Date(timestamp * 1000);
    return `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, '0')}`;
}

export function formatMonthLabel(key: string): string {
    const [year, month] = key.split('-').map(Number);
    if (!year || !month) {
        return 'Unknown';
    }

    return new Intl.DateTimeFormat(undefined, {
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(Date.UTC(year, month - 1, 1)));
}
