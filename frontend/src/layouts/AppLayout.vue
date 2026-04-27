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
                            <RouterLink :to="{ name: 'dashboard' }" class="lex-nav-btn">
                                <i class="ri-home-line" aria-hidden="true" />
                                {{ t('navigation.home') }}
                            </RouterLink>
                            <div ref="districtsRoot" class="lex-nav-districts">
                                <button
                                    type="button"
                                    class="lex-nav-btn"
                                    :aria-expanded="isDistrictsOpen ? 'true' : 'false'"
                                    @click="toggleDistrictsMenu"
                                >
                                    <i class="ri-map-pin-2-line" aria-hidden="true" />
                                    {{ t('navigation.districts') }}
                                    <i class="ri-arrow-down-s-line lex-app-account-trigger-icon" aria-hidden="true" />
                                </button>
                                <div v-if="isDistrictsOpen" class="lex-app-account-dropdown">
                                    <RouterLink
                                        v-for="district in navDistricts"
                                        :key="district.ulid"
                                        :to="{ name: 'district', params: { district: district.ulid } }"
                                        class="lex-app-account-dropdown-item"
                                        @click="isDistrictsOpen = false"
                                    >
                                        {{ t('district.number_label', { number: district.number }) }}
                                    </RouterLink>
                                    <div v-if="navDistricts.length === 0" class="lex-app-account-dropdown-empty">
                                        {{ t('dashboard.no_districts') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="lex-app-account-identity">
                            <div ref="notifRoot" class="lex-notif-bell">
                                <button
                                    type="button"
                                    class="lex-nav-btn lex-nav-btn-icon lex-notif-trigger"
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

                            <div ref="accountMenuRoot" class="lex-app-account-menu">
                                <button
                                    ref="menuTrigger"
                                    type="button"
                                    class="lex-nav-btn lex-app-account-trigger"
                                    :aria-label="t('app.account_menu')"
                                    aria-haspopup="true"
                                    :aria-expanded="isAccountMenuOpen ? 'true' : 'false'"
                                    @click="toggleAccountMenu"
                                >
                                    <i class="ri-user-line lex-app-account-user-icon" aria-hidden="true" />
                                    <span v-if="displayName !== ''" class="lex-app-account-name">{{ displayName }}</span>
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
                                        <i class="ri-user-line" aria-hidden="true" />
                                        {{ t('navigation.profile') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="lex-app-account-dropdown-item"
                                        role="menuitem"
                                        @click="navigateToAccountRoute('preferences')"
                                    >
                                        <i class="ri-equalizer-line" aria-hidden="true" />
                                        {{ t('navigation.preferences') }}
                                    </button>
                                    <template v-if="showMailpit">
                                        <div class="lex-app-account-dropdown-divider" />
                                        <a
                                            href="/mail/"
                                            class="lex-app-account-dropdown-item"
                                            role="menuitem"
                                        >
                                            <i class="ri-mail-line" aria-hidden="true" />
                                            {{ t('app.mailpit') }}
                                        </a>
                                    </template>
                                    <div class="lex-app-account-dropdown-divider" />
                                    <button
                                        type="button"
                                        class="lex-app-account-dropdown-item lex-app-account-dropdown-item-danger"
                                        role="menuitem"
                                        @click="onSignOut"
                                    >
                                        <i class="ri-logout-box-r-line" aria-hidden="true" />
                                        {{ t('app.logout') }}
                                    </button>
                                </div>
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
                                <i v-if="item.icon" :class="item.icon" aria-hidden="true" />
                                {{ item.label }}
                            </RouterLink>
                        </li>
                        <li v-else class="lex-app-breadcrumb-current">
                            <i v-if="item.icon" :class="item.icon" aria-hidden="true" />
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
                <span>&copy; {{ t('app.name') }}</span>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import type { DistrictSummary } from '@/api/districts';
import { fetchAccessibleDistricts } from '@/api/districts';
import { useAuthStore } from '@/stores/auth';
import { useNotificationsStore } from '@/stores/notifications';
import { useBreadcrumbStore } from '@/stores/breadcrumb';
import NotificationDropdown from '@/components/ui/NotificationDropdown.vue';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const showMailpit = computed(() => import.meta.env.DEV && authStore.isAppAdmin);
const isAccountMenuOpen = ref(false);
const accountMenuRoot = ref<HTMLElement | null>(null);
const menuTrigger = ref<HTMLButtonElement | null>(null);
const firstMenuItem = ref<HTMLButtonElement | null>(null);
const notificationsStore = useNotificationsStore();
const breadcrumbStore = useBreadcrumbStore();
const isNotifOpen = ref(false);
const notifRoot = ref<HTMLElement | null>(null);
const isDistrictsOpen = ref(false);
const districtsRoot = ref<HTMLElement | null>(null);
const navDistrictsRaw = ref<DistrictSummary[]>([]);
const navDistricts = computed(() =>
    navDistrictsRaw.value.filter((d) => !d.disabled).sort((a, b) => a.number - b.number),
);

const displayName = computed(() => [authStore.user?.name, authStore.user?.surname].filter((value): value is string => typeof value === 'string' && value !== '').join(' '));

type BreadcrumbItem = { label: string; to?: object; icon?: string };

const breadcrumbs = computed((): BreadcrumbItem[] => {
    const items: BreadcrumbItem[] = [{ label: t('navigation.home'), to: { name: 'dashboard' }, icon: 'ri-home-line' }];
    const districtParam = String(route.params.district ?? '');
    const districtLabel = breadcrumbStore.districtLabel
        ?? (breadcrumbStore.districtNumber !== null ? t('district.number_label', { number: breadcrumbStore.districtNumber }) : '...');

    if (route.name === 'profile') {
        items.push({ label: t('navigation.profile') });
    } else if (route.name === 'preferences') {
        items.push({ label: t('navigation.preferences') });
    } else if (route.name === 'district') {
        items.push({ label: districtLabel });
    } else if (route.name === 'debtors' || route.name === 'debtor-create') {
        items.push({ label: districtLabel, to: { name: 'district', params: { district: districtParam } } });
        items.push({ label: t('navigation.debtors') });
    } else if (route.name === 'debtor') {
        items.push({ label: districtLabel, to: { name: 'district', params: { district: districtParam } } });
        items.push({ label: t('navigation.debtors'), to: { name: 'debtors', params: { district: districtParam } } });
        items.push({ label: breadcrumbStore.debtorLabel ?? '...' });
    } else if (route.name === 'debt') {
        const debtorParam = String(route.params.debtor ?? '');
        items.push({ label: districtLabel, to: { name: 'district', params: { district: districtParam } } });
        items.push({ label: t('navigation.debtors'), to: { name: 'debtors', params: { district: districtParam } } });
        items.push({ label: breadcrumbStore.debtorLabel ?? '...', to: { name: 'debtor', params: { district: districtParam, debtor: debtorParam } } });
        items.push({ label: breadcrumbStore.debtLabel ?? t('navigation.debt') });
    } else if (route.name === 'payments' || route.name === 'payment-create') {
        const debtorParam = String(route.params.debtor ?? '');
        const debtParam = String(route.params.debt ?? '');
        items.push({ label: districtLabel, to: { name: 'district', params: { district: districtParam } } });
        items.push({ label: t('navigation.debtors'), to: { name: 'debtors', params: { district: districtParam } } });
        items.push({ label: breadcrumbStore.debtorLabel ?? '...', to: { name: 'debtor', params: { district: districtParam, debtor: debtorParam } } });
        items.push({ label: breadcrumbStore.debtLabel ?? t('navigation.debt'), to: { name: 'debt', params: { district: districtParam, debtor: debtorParam, debt: debtParam } } });
        items.push({ label: t('navigation.payments') });
    } else if (route.name === 'payment-show' || route.name === 'payment-edit') {
        const debtorParam = String(route.params.debtor ?? '');
        const debtParam = String(route.params.debt ?? '');
        items.push({ label: districtLabel, to: { name: 'district', params: { district: districtParam } } });
        items.push({ label: t('navigation.debtors'), to: { name: 'debtors', params: { district: districtParam } } });
        items.push({ label: breadcrumbStore.debtorLabel ?? '...', to: { name: 'debtor', params: { district: districtParam, debtor: debtorParam } } });
        items.push({ label: breadcrumbStore.debtLabel ?? t('navigation.debt'), to: { name: 'debt', params: { district: districtParam, debtor: debtorParam, debt: debtParam } } });
        items.push({ label: t('navigation.payments'), to: { name: 'payments', params: { district: districtParam, debtor: debtorParam, debt: debtParam } } });
        items.push({ label: breadcrumbStore.paymentLabel ?? '...' });
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

watchEffect(() => {
    const last = breadcrumbs.value[breadcrumbs.value.length - 1];
    if (last && last.label !== '...') {
        document.title = `Executo | ${last.label}`;
    }
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

function toggleDistrictsMenu(): void {
    isDistrictsOpen.value = !isDistrictsOpen.value;
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

    if (districtsRoot.value?.contains(target) !== true) {
        isDistrictsOpen.value = false;
    }
}

onMounted(async () => {
    document.addEventListener('mousedown', onDocumentPointerDown);
    notificationsStore.startPolling();

    try {
        navDistrictsRaw.value = await fetchAccessibleDistricts();
    } catch {
        // silent — nav will show empty state
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocumentPointerDown);
    notificationsStore.stopPolling();
});

function onSignOut(): void {
    closeAccountMenu();
    window.location.assign('/login');
    authStore.signOut().catch(() => undefined);
}
</script>
