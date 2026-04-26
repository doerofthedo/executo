import { ref } from 'vue';
import { defineStore } from 'pinia';
import { fetchNotifications, markNotificationRead, markAllNotificationsRead, type AppNotification } from '@/api/notifications';

export const useNotificationsStore = defineStore('notifications', () => {
    const notifications = ref<AppNotification[]>([]);
    const unreadCount = ref(0);
    let pollInterval: ReturnType<typeof setInterval> | null = null;

    async function load(): Promise<void> {
        try {
            const result = await fetchNotifications();
            notifications.value = result.data;
            unreadCount.value = result.unread_count;
        } catch {
            // Silently ignore — notification polling should never break the app
        }
    }

    async function markRead(id: string): Promise<void> {
        await markNotificationRead(id);
        const notification = notifications.value.find((n) => n.id === id);
        if (notification !== undefined) {
            notification.read_at = new Date().toISOString();
        }
        unreadCount.value = notifications.value.filter((n) => n.read_at === null).length;
    }

    async function markAllRead(): Promise<void> {
        await markAllNotificationsRead();
        const now = new Date().toISOString();
        notifications.value.forEach((n) => { n.read_at = n.read_at ?? now; });
        unreadCount.value = 0;
    }

    function startPolling(): void {
        void load();
        pollInterval = setInterval(() => { void load(); }, 60_000);
    }

    function stopPolling(): void {
        if (pollInterval !== null) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    return { notifications, unreadCount, load, markRead, markAllRead, startPolling, stopPolling };
});
