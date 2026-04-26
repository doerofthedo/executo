<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-overview-hero-dark">
                <div class="lex-overview-hero-body">
                    <div class="lex-overview-hero-header">
                        <div>
                            <p class="lex-overview-eyebrow">{{ t('district.eyebrow') }}</p>
                            <h1 class="lex-overview-title">{{ heroTitle }}</h1>
                            <p v-if="stats?.district.court" class="mt-2 text-sm text-slate-400">
                                {{ stats.district.court }}
                            </p>
                        </div>

                        <div v-if="stats" class="lex-overview-metric-grid-dark">
                            <article class="lex-overview-metric-card-dark">
                                <p class="lex-overview-metric-label">{{ t('district.stats_debtors') }}</p>
                                <p class="lex-overview-metric-value">{{ stats.debtors_count }}</p>
                            </article>
                            <article class="lex-overview-metric-card-dark">
                                <p class="lex-overview-metric-label">{{ t('district.stats_debts') }}</p>
                                <p class="lex-overview-metric-value">{{ stats.debts_count }}</p>
                            </article>
                            <article class="lex-overview-metric-card-dark">
                                <p class="lex-overview-metric-label">{{ t('district.stats_payments') }}</p>
                                <p class="lex-overview-metric-value">{{ stats.payments_count }}</p>
                            </article>
                            <article v-if="stats.can_view_users_count" class="lex-overview-metric-card-dark">
                                <p class="lex-overview-metric-label">{{ t('district.stats_users') }}</p>
                                <p class="lex-overview-metric-value">{{ stats.users_count ?? 0 }}</p>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            <div v-if="loading" class="lex-panel p-8 text-sm text-slate-500">
                {{ t('district.loading') }}
            </div>

            <div v-else-if="loadError" class="lex-panel p-8">
                <p class="lex-form-message lex-form-message-error">{{ t('district.load_error') }}</p>
            </div>

            <template v-else-if="stats">
                <section class="lex-panel p-8">
                    <div class="flex flex-wrap gap-3">
                        <RouterLink
                            :to="{ name: 'debtors', params: { district: districtUlid } }"
                            class="lex-button lex-button-primary"
                        >
                            {{ t('district.open_debtors') }}
                        </RouterLink>

                        <RouterLink
                            v-if="stats.can_manage_users"
                            :to="{ name: 'user-management', params: { district: districtUlid } }"
                            class="lex-button lex-button-secondary"
                        >
                            {{ t('district.open_users') }}
                        </RouterLink>

                        <RouterLink
                            v-if="stats.can_create_debtor"
                            :to="{ name: 'debtor-create', params: { district: districtUlid } }"
                            class="lex-button lex-button-secondary"
                        >
                            {{ t('district.create_debtor') }}
                        </RouterLink>
                    </div>
                </section>

                <section class="lex-panel p-8">
                    <h2 class="mb-5 text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                        {{ t('district.details_title') }}
                    </h2>
                    <dl class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="space-y-1">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                {{ t('district.bailiff') }}
                            </dt>
                            <dd class="text-sm text-slate-800">{{ bailiffName || t('district.none') }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                {{ t('district.court') }}
                            </dt>
                            <dd class="text-sm text-slate-800">{{ stats.district.court || t('district.none') }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                {{ t('district.address') }}
                            </dt>
                            <dd class="text-sm text-slate-800">{{ stats.district.address || t('district.none') }}</dd>
                        </div>
                    </dl>
                </section>
            </template>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { fetchDistrictStats, type DistrictStats } from '@/api/districts';
import { useBreadcrumbStore } from '@/stores/breadcrumb';

const { t } = useI18n();
const route = useRoute();
const breadcrumbStore = useBreadcrumbStore();

const districtUlid = computed(() => String(route.params.district ?? ''));
const loading = ref(true);
const loadError = ref(false);
const stats = ref<DistrictStats | null>(null);

const heroTitle = computed(() => {
    if (!stats.value) {
        return t('district.eyebrow');
    }

    const { bailiff_name, bailiff_surname } = stats.value.district;
    const name = [bailiff_name, bailiff_surname].filter(Boolean).join(' ');

    return name || t('district.default_title', { number: stats.value.district.number });
});

const bailiffName = computed(() => {
    if (!stats.value) {
        return null;
    }

    const { bailiff_name, bailiff_surname } = stats.value.district;

    return [bailiff_name, bailiff_surname].filter(Boolean).join(' ') || null;
});

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = false;

    try {
        stats.value = await fetchDistrictStats(districtUlid.value);
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

watch(stats, (value) => {
    if (value) {
        breadcrumbStore.districtLabel = t('district.number_label', { number: value.district.number });
    }
});

onMounted(load);

onUnmounted(() => {
    breadcrumbStore.districtLabel = null;
});
</script>
