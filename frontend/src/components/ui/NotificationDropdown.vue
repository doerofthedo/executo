<template>
    <div class="lex-notif-dropdown" role="menu" :aria-label="t('notifications.menu_label')" @keydown.esc.prevent="emit('close')">
        <div class="lex-notif-header">
            <span class="lex-notif-header-title">{{ t('notifications.title') }}</span>
            <button
                v-if="store.unreadCount > 0"
                type="button"
                class="lex-button-inline lex-button lex-notif-mark-all"
                @click="onMarkAllRead"
            >
                {{ t('notifications.mark_all_read') }}
            </button>
        </div>

        <div v-if="store.notifications.length === 0" class="lex-notif-empty">
            {{ t('notifications.empty') }}
        </div>

        <ul v-else class="lex-notif-list">
            <li
                v-for="notification in store.notifications"
                :key="notification.id"
                class="lex-notif-item"
                :class="{ 'lex-notif-item--unread': notification.read_at === null }"
                role="menuitem"
                @click="onMarkRead(notification.id)"
            >
                <div class="lex-notif-item-row">
                    <i class="ri-user-line lex-notif-icon" aria-hidden="true" />
                    <div class="lex-notif-item-body">
                        <p class="lex-notif-item-message" v-html="formatMessage(notification)" />
                        <p class="lex-notif-item-time">{{ formatTime(notification.created_at) }}</p>
                    </div>
                    <span v-if="notification.read_at === null" class="lex-notif-dot" aria-hidden="true" />
                </div>
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useNotificationsStore } from '@/stores/notifications';
import type { AppNotification } from '@/api/notifications';

const emit = defineEmits<{ close: [] }>();

const { t } = useI18n();
const store = useNotificationsStore();

function formatMessage(notification: AppNotification): string {
    if (notification.type === 'user.name_changed') {
        const oldName = notification.data.old_name ?? '?';
        const newName = notification.data.new_name ?? '?';
        return t('notifications.name_changed', { old_name: oldName, new_name: newName });
    }
    return t('notifications.unknown');
}

function formatTime(isoString: string): string {
    const date = new Date(isoString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMin = Math.floor(diffMs / 60_000);

    if (diffMin < 1) return t('notifications.just_now');
    if (diffMin < 60) return t('notifications.minutes_ago', { n: diffMin });

    const diffH = Math.floor(diffMin / 60);
    if (diffH < 24) return t('notifications.hours_ago', { n: diffH });

    const diffD = Math.floor(diffH / 24);
    return t('notifications.days_ago', { n: diffD });
}

async function onMarkRead(id: string): Promise<void> {
    await store.markRead(id);
}

async function onMarkAllRead(): Promise<void> {
    await store.markAllRead();
}
</script>
