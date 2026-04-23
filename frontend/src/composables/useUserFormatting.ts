import dayjs from 'dayjs';
import timezone from 'dayjs/plugin/timezone';
import utc from 'dayjs/plugin/utc';
import { storeToRefs } from 'pinia';
import { usePreferencesStore } from '@/stores/preferences';

dayjs.extend(utc);
dayjs.extend(timezone);

export function useUserFormatting() {
    const preferencesStore = usePreferencesStore();
    const { dateFormat, timezone: userTimezone } = storeToRefs(preferencesStore);

    function formatDate(value: string): string {
        return dayjs.utc(value).tz(userTimezone.value).format(dateFormat.value);
    }

    return {
        formatDate,
    };
}
