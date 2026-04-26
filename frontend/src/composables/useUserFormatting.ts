import dayjs from 'dayjs';
import timezone from 'dayjs/plugin/timezone';
import utc from 'dayjs/plugin/utc';
import { storeToRefs } from 'pinia';
import { usePreferencesStore } from '@/stores/preferences';

dayjs.extend(utc);
dayjs.extend(timezone);

export function useUserFormatting() {
    const preferencesStore = usePreferencesStore();
    const { dateFormat, timezone: userTimezone, decimalSeparator, thousandSeparator } = storeToRefs(preferencesStore);

    function formatDate(value: string): string {
        return dayjs.utc(value).tz(userTimezone.value).format(dateFormat.value);
    }

    function formatAmount(value: string | number | null | undefined, decimals = 2): string {
        if (value === null || value === undefined || value === '') {
            return '—';
        }

        const numeric = typeof value === 'string' ? parseFloat(value) : value;

        if (!Number.isFinite(numeric)) {
            return '—';
        }

        const sign = numeric < 0 ? '-' : '';
        const absolute = Math.abs(numeric);
        const [intPart, fracPart] = absolute.toFixed(decimals).split('.');
        const grouped = thousandSeparator.value
            ? intPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator.value)
            : intPart;

        return `${sign}${grouped}${decimalSeparator.value}${fracPart}`;
    }

    return {
        formatDate,
        formatAmount,
    };
}
