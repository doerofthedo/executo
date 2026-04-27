<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-overview-hero-dark">
                <div class="lex-overview-hero-body">
                    <div class="lex-overview-hero-header">
                        <div>
                            <h1 class="lex-overview-title">{{ heroTitle }}</h1>
                        </div>

                        <div class="lex-overview-metric-grid-dark">
                            <article v-for="item in heroStats" :key="item.label" class="lex-overview-metric-card-dark">
                                <p class="lex-overview-metric-label">{{ t(item.label) }}</p>
                                <p class="lex-overview-metric-value">{{ item.value }}</p>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            <EmailVerificationBanner />

            <section class="lex-dashboard-main-grid">
                <div class="lex-panel lex-dashboard-section">
                    <div class="lex-dashboard-section-header">
                        <h2 class="lex-page-title lex-dashboard-section-title">{{ t('dashboard.districts_title') }}</h2>
                    </div>

                    <div v-if="loading" class="lex-dashboard-empty">
                        {{ t('dashboard.loading') }}
                    </div>

                    <div v-else-if="loadError !== ''" class="lex-form-message lex-form-message-error lex-dashboard-status">
                        {{ loadError }}
                    </div>

                    <div v-else-if="districtCards.length === 0" class="lex-dashboard-empty">
                        {{ t('dashboard.no_districts') }}
                    </div>

                    <div v-else class="lex-district-card-grid">
                        <article
                            v-for="card in districtCards"
                            :key="card.district.ulid"
                            class="lex-district-card"
                        >
                            <div class="lex-district-card-inner">
                                <div class="lex-district-card-main">
                                    <p class="lex-district-card-eyebrow">
                                        {{ districtLabel(card) }}
                                        <template v-if="card.district.court">
                                            <span class="lex-district-card-eyebrow-sep">·</span>
                                            {{ card.district.court }}
                                        </template>
                                    </p>
                                    <h2 class="lex-district-card-title">{{ districtTitle(card) }}</h2>
                                    <RouterLink
                                        :to="{ name: 'district', params: { district: card.district.ulid } }"
                                        class="lex-button lex-button-secondary lex-district-card-open"
                                    >
                                        <i class="ri-login-circle-line" aria-hidden="true" />
                                        {{ t('dashboard.open_district') }}
                                    </RouterLink>
                                </div>

                                <dl class="lex-district-card-stats">
                                    <div v-if="card.can_view_users_count" class="lex-overview-metric-card">
                                        <dt class="lex-overview-metric-label">{{ t('dashboard.stats_users') }}</dt>
                                        <dd class="lex-overview-metric-value">{{ card.users_count ?? 0 }}</dd>
                                    </div>
                                    <div class="lex-overview-metric-card">
                                        <dt class="lex-overview-metric-label">{{ t('dashboard.stats_debtors') }}</dt>
                                        <dd class="lex-overview-metric-value">{{ card.debtors_count }}</dd>
                                    </div>
                                    <div class="lex-overview-metric-card">
                                        <dt class="lex-overview-metric-label">{{ t('dashboard.stats_debts') }}</dt>
                                        <dd class="lex-overview-metric-value">{{ card.debts_count }}</dd>
                                    </div>
                                    <div class="lex-overview-metric-card">
                                        <dt class="lex-overview-metric-label">{{ t('dashboard.stats_payments') }}</dt>
                                        <dd class="lex-overview-metric-value">{{ card.payments_count }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </article>
                    </div>
                </div>

                <aside class="lex-panel lex-dashboard-actions-panel">
                    <div class="lex-dashboard-section-header">
                        <h2 class="lex-page-title lex-dashboard-section-title">{{ t('dashboard.quick_actions_title') }}</h2>
                    </div>

                    <div v-if="effectiveDefaultDistrict === null" class="lex-dashboard-empty">
                        {{ t('dashboard.no_districts') }}
                    </div>

                    <div v-else class="lex-dashboard-actions-stack">
                        <p class="lex-key-value-label">{{ t('dashboard.quick_actions_context', { district: districtLabel(effectiveDefaultDistrict) }) }}</p>

                        <RouterLink
                            v-if="effectiveDefaultDistrict.can_manage_users"
                            :to="{ name: 'user-management', params: { district: effectiveDefaultDistrict.district.ulid } }"
                            class="lex-button lex-button-primary lex-dashboard-action-button"
                        >
                            <i class="ri-team-line" aria-hidden="true" />
                            {{ t('dashboard.manage_users') }}
                        </RouterLink>

                        <RouterLink
                            v-if="effectiveDefaultDistrict.can_create_debtor"
                            :to="{ name: 'debtor-create', params: { district: effectiveDefaultDistrict.district.ulid } }"
                            class="lex-button lex-button-secondary lex-dashboard-action-button"
                        >
                            <i class="ri-user-add-line" aria-hidden="true" />
                            {{ t('dashboard.add_debtor') }}
                        </RouterLink>

                        <RouterLink
                            v-if="effectiveDefaultDistrict.can_create_debt"
                            :to="{ name: 'debt-create', params: { district: effectiveDefaultDistrict.district.ulid } }"
                            class="lex-button lex-button-secondary lex-dashboard-action-button"
                        >
                            <i class="ri-file-add-line" aria-hidden="true" />
                            {{ t('dashboard.add_debt') }}
                        </RouterLink>

                        <RouterLink
                            v-if="effectiveDefaultDistrict.can_create_payment"
                            :to="{ name: 'operation-create-payment', params: { district: effectiveDefaultDistrict.district.ulid } }"
                            class="lex-button lex-button-secondary lex-dashboard-action-button"
                        >
                            <i class="ri-cash-line" aria-hidden="true" />
                            {{ t('dashboard.add_payment') }}
                        </RouterLink>


                    </div>
                </aside>
            </section>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import dayjs from 'dayjs';
import timezone from 'dayjs/plugin/timezone';
import utc from 'dayjs/plugin/utc';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import type { DashboardDistrictCard, DashboardStats } from '@/api/dashboard';
import { getDashboardStats } from '@/api/dashboard';
import { saveDefaultDistrict } from '@/api/users';
import AppLayout from '@/layouts/AppLayout.vue';
import EmailVerificationBanner from '@/components/domain/EmailVerificationBanner.vue';
import { useAuthStore } from '@/stores/auth';
import { usePreferencesStore } from '@/stores/preferences';
import { formatLatvianVocativeFirstName } from '@/utils/latvianNameDeclension';

dayjs.extend(utc);
dayjs.extend(timezone);

const { locale, t } = useI18n();
const authStore = useAuthStore();
const preferencesStore = usePreferencesStore();


const stats = ref<DashboardStats>({
    data: [],
    districts_count: 0,
    debtors_count: 0,
    debts_count: 0,
    payments_count: 0,
});
const loading = ref(true);
const loadError = ref('');
const currentTime = ref(dayjs());
let currentTimeInterval: number | null = null;

const firstName = computed(() => authStore.user?.name?.split(' ')[0] ?? '');
const greetingName = computed(() => {
    if (firstName.value === '') {
        return '';
    }

    return locale.value === 'lv'
        ? formatLatvianVocativeFirstName(firstName.value)
        : firstName.value;
});
const greetingKey = computed(() => {
    if (locale.value !== 'lv') {
        return firstName.value === '' ? 'dashboard.fallback' : 'dashboard.greeting';
    }

    const localHour = currentTime.value.tz(preferencesStore.timezone).hour();

    if (localHour < 12) {
        return firstName.value === '' ? 'dashboard.fallback_morning' : 'dashboard.greeting_morning';
    }

    if (localHour < 18) {
        return firstName.value === '' ? 'dashboard.fallback_afternoon' : 'dashboard.greeting_afternoon';
    }

    return firstName.value === '' ? 'dashboard.fallback_evening' : 'dashboard.greeting_evening';
});
const heroTitle = computed(() => {
    return greetingName.value !== ''
        ? t(greetingKey.value, { name: greetingName.value })
        : t(greetingKey.value);
});
const heroStats = computed(() => [
    { label: 'dashboard.stats_districts', value: String(stats.value.districts_count) },
    { label: 'dashboard.stats_debtors', value: String(stats.value.debtors_count) },
    { label: 'dashboard.stats_debts', value: String(stats.value.debts_count) },
    { label: 'dashboard.stats_payments', value: String(stats.value.payments_count) },
]);
const districtCards = computed(() => {
    return [...stats.value.data].sort((left, right) => left.district.number - right.district.number);
});
const validDistrictCards = computed(() => districtCards.value.filter((card) => !card.district.disabled));
const effectiveDefaultDistrict = computed(() => {
    const preferredUlid = authStore.user?.default_district_ulid ?? null;

    if (preferredUlid !== null) {
        const preferred = validDistrictCards.value.find((card) => card.district.ulid === preferredUlid);
        if (preferred) return preferred;
    }

    return validDistrictCards.value[0] ?? null;
});

function districtLabel(card: DashboardDistrictCard): string {
    return t('dashboard.district_label', { number: card.district.number });
}

function districtTitle(card: DashboardDistrictCard): string {
    const parts = [card.district.bailiff_name, card.district.bailiff_surname].filter((part): part is string => part !== null && part !== '');

    return parts.join(' ').trim() || districtLabel(card);
}

onMounted(async () => {
    currentTimeInterval = window.setInterval(() => {
        currentTime.value = dayjs();
    }, 60_000);

    loading.value = true;
    loadError.value = '';

    try {
        stats.value = await getDashboardStats();

        const user = authStore.user;
        const preferredUlid = user?.default_district_ulid ?? null;
        const isPreferredValid = preferredUlid !== null
            && validDistrictCards.value.some((card) => card.district.ulid === preferredUlid);

        if (!isPreferredValid && effectiveDefaultDistrict.value !== null && user?.ulid) {
            const newUlid = effectiveDefaultDistrict.value.district.ulid;

            try {
                await saveDefaultDistrict(user.ulid, newUlid);
                user.default_district_ulid = newUlid;
            } catch {
                // silent — will retry next load
            }
        }
    } catch {
        loadError.value = t('dashboard.load_error');
    } finally {
        loading.value = false;
    }
});

onUnmounted(() => {
    if (currentTimeInterval !== null) {
        window.clearInterval(currentTimeInterval);
    }
});
</script>
