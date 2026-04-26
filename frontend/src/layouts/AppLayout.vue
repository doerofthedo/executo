<template>
    <div class="lex-app-shell">
        <header class="lex-app-header">
            <div class="lex-app-header-inner">
                <div class="lex-app-nav-row">
                    <RouterLink :to="{ name: 'dashboard' }" class="lex-brand">
                        {{ t('app.name') }}
                    </RouterLink>

                    <div class="lex-app-account">
                        <div class="lex-app-account-actions">
                            <RouterLink :to="{ name: 'dashboard' }" class="lex-button lex-button-secondary">
                                {{ t('navigation.dashboard') }}
                            </RouterLink>
                            <a v-if="showMailpit" href="/mail/" class="lex-button lex-button-secondary">
                                {{ t('app.mailpit') }}
                            </a>

                            <div ref="notifRoot" class="lex-notif-bell">
                                <button
                                    type="button"
                                    class="lex-button lex-button-secondary lex-notif-trigger"
                                    :aria-label="t('notifications.menu_label')"
                                    :aria-expanded="isNotifOpen ? 'true' : 'false'"
                                    @click="toggleNotifDropdown"
                                >
                                    <i class="ri-notification-3-line" aria-hidden="true" />
                                    <span v-if="notificationsStore.unreadCount > 0" class="lex-notif-badge">
                                        {{ notificationsStore.unreadCount > 99 ? '99+' : notificationsStore.unreadCount }}
                                    </span>
                                </button>

                                <NotificationDropdown
                                    v-if="isNotifOpen"
                                    @close="isNotifOpen = false"
                                />
                            </div>
                        </div>
                        <div ref="accountMenuRoot" class="lex-app-account-menu">
                            <button
                                ref="menuTrigger"
                                type="button"
                                class="lex-app-account-trigger"
                                :aria-label="t('app.account_menu')"
                                aria-haspopup="true"
                                :aria-expanded="isAccountMenuOpen ? 'true' : 'false'"
                                @click="toggleAccountMenu"
                            >
                                <div class="lex-app-account-copy" v-if="displayName !== ''">
                                    <p class="lex-app-account-label">{{ t('app.signed_in_as') }}</p>
                                    <p class="lex-app-account-name">{{ displayName }}</p>
                                </div>
                                <i class="ri-arrow-down-s-line lex-app-account-trigger-icon" aria-hidden="true" />
                            </button>

                            <div
                                v-if="isAccountMenuOpen"
                                class="lex-app-account-dropdown"
                                role="menu"
                                :aria-label="t('app.account_menu')"
                                @keydown.esc.prevent="closeAccountMenuAndFocusTrigger"
                            >
                                <button
                                    ref="firstMenuItem"
                                    type="button"
                                    class="lex-app-account-dropdown-item"
                                    role="menuitem"
                                    @click="navigateToAccountRoute('profile')"
                                >
                                    {{ t('navigation.profile') }}
                                </button>
                                <button
                                    type="button"
                                    class="lex-app-account-dropdown-item"
                                    role="menuitem"
                                    @click="navigateToAccountRoute('preferences')"
                                >
                                    {{ t('navigation.preferences') }}
                                </button>
                                <div class="lex-app-account-dropdown-divider" />
                                <button
                                    type="button"
                                    class="lex-app-account-dropdown-item lex-app-account-dropdown-item-danger"
                                    role="menuitem"
                                    @click="onSignOut"
                                >
                                    {{ t('app.logout') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="lex-app-breadcrumb-bar">
            <div class="lex-app-breadcrumb-inner">
                <ol class="lex-app-breadcrumb-list">
                    <template v-for="(item, index) in breadcrumbs" :key="`${item.label}-${index}`">
                        <li v-if="index > 0">/</li>
                        <li v-if="item.to">
                            <RouterLink :to="item.to" class="lex-app-breadcrumb-link">
                                {{ item.label }}
                            </RouterLink>
                        </li>
                        <li v-else class="lex-app-breadcrumb-current">
                            {{ item.label }}
                        </li>
                    </template>
                </ol>
            </div>
        </div>

        <main class="lex-app-main">
            <section class="lex-app-content">
                <slot />
            </section>
        </main>

        <footer class="lex-app-footer">
            <div class="lex-app-footer-inner">
                <span>{{ t('app.footer') }}</span>
                <span v-if="showMailpit">{{ t('app.mailpit_hint') }}</span>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useNotificationsStore } from '@/stores/notifications';
import { useBreadcrumbStore } from '@/stores/breadcrumb';
import NotificationDropdown from '@/components/ui/NotificationDropdown.vue';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const showMailpit = import.meta.env.DEV;
const isAccountMenuOpen = ref(false);
const accountMenuRoot = ref<HTMLElement | null>(null);
const menuTrigger = ref<HTMLButtonElement | null>(null);
const firstMenuItem = ref<HTMLButtonElement | null>(null);
const notificationsStore = useNotificationsStore();
const breadcrumbStore = useBreadcrumbStore();
const isNotifOpen = ref(false);
const notifRoot = ref<HTMLElement | null>(null);

const displayName = computed(() => [authStore.user?.name, authStore.user?.surname].filter((value): value is string => typeof value === 'string' && value !== '').join(' '));

type BreadcrumbItem = { label: string; to?: object };

const breadcrumbs = computed((): BreadcrumbItem[] => {
    const items: BreadcrumbItem[] = [{ label: t('navigation.dashboard'), to: { name: 'dashboard' } }];
    const districtParam = String(route.params.district ?? '');
    const districtLabel = breadcrumbStore.districtLabel ?? districtParam;

    if (route.name === 'profile') {
        items.push({ label: t('navigation.profile') });
    } else if (route.name === 'preferences') {
        items.push({ label: t('navigation.preferences') });
    } else if (route.name === 'district') {
        items.push({ label: districtLabel });
    } else if (route.name === 'customers' || route.name === 'customer-create') {
        items.push({ label: districtLabel, to: { name: 'district', params: { district: districtParam } } });
        items.push({ label: t('navigation.customers') });
    } else if (route.name === 'customer') {
        items.push({ label: districtLabel, to: { name: 'district', params: { district: districtParam } } });
        items.push({ label: t('navigation.customers'), to: { name: 'customers', params: { district: districtParam } } });
        items.push({ label: breadcrumbStore.customerLabel ?? '...' });
    } else if (route.name === 'debt') {
        const customerParam = String(route.params.customer ?? '');
        items.push({ label: districtLabel, to: { name: 'district', params: { district: districtParam } } });
        items.push({ label: t('navigation.customers'), to: { name: 'customers', params: { district: districtParam } } });
        items.push({ label: breadcrumbStore.customerLabel ?? '...', to: { name: 'customer', params: { district: districtParam, customer: customerParam } } });
        items.push({ label: breadcrumbStore.debtLabel ?? t('navigation.debt') });
    } else if (route.name === 'payments') {
        const customerParam = String(route.params.customer ?? '');
        const debtParam = String(route.params.debt ?? '');
        items.push({ label: districtLabel, to: { name: 'district', params: { district: districtParam } } });
        items.push({ label: t('navigation.customers'), to: { name: 'customers', params: { district: districtParam } } });
        items.push({ label: breadcrumbStore.customerLabel ?? '...', to: { name: 'customer', params: { district: districtParam, customer: customerParam } } });
        items.push({ label: breadcrumbStore.debtLabel ?? t('navigation.debt'), to: { name: 'debt', params: { district: districtParam, customer: customerParam, debt: debtParam } } });
        items.push({ label: t('navigation.payments') });
    } else if (route.name === 'user-management') {
        items.push({ label: districtLabel, to: { name: 'district', params: { district: districtParam } } });
        items.push({ label: t('navigation.user_management') });
    } else if (route.name === 'district-user-create') {
        items.push({ label: districtLabel, to: { name: 'district', params: { district: districtParam } } });
        items.push({ label: t('navigation.user_management'), to: { name: 'user-management', params: { district: districtParam } } });
        items.push({ label: t('user_management.invite_title') });
    }

    return items;
});

function closeAccountMenu(): void {
    isAccountMenuOpen.value = false;
}

function closeAccountMenuAndFocusTrigger(): void {
    closeAccountMenu();
    menuTrigger.value?.focus();
}

async function toggleAccountMenu(): Promise<void> {
    isAccountMenuOpen.value = !isAccountMenuOpen.value;

    if (isAccountMenuOpen.value) {
        await nextTick();
        firstMenuItem.value?.focus();
    }
}

async function navigateToAccountRoute(name: 'profile' | 'preferences'): Promise<void> {
    closeAccountMenu();
    await router.push({ name });
}

function toggleNotifDropdown(): void {
    isNotifOpen.value = !isNotifOpen.value;
}

function onDocumentPointerDown(event: MouseEvent): void {
    const target = event.target;

    if (!(target instanceof Node)) {
        return;
    }

    if (accountMenuRoot.value?.contains(target) !== true) {
        closeAccountMenu();
    }

    if (notifRoot.value?.contains(target) !== true) {
        isNotifOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('mousedown', onDocumentPointerDown);
    notificationsStore.startPolling();
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocumentPointerDown);
    notificationsStore.stopPolling();
});

async function onSignOut(): Promise<void> {
    closeAccountMenu();
    await authStore.signOut();
    window.location.assign('/login');
}
</script>
