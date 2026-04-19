import dayjs from 'dayjs';
import { storeToRefs } from 'pinia';
import { usePreferencesStore } from '@/stores/preferences';

export function useUserFormatting() {
    const preferencesStore = usePreferencesStore();
    const { dateFormat } = storeToRefs(preferencesStore);

    function formatDate(value: string): string {
        return dayjs(value).format(dateFormat.value);
    }

    return {
        formatDate,
    };
}
