<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-overview-hero-dark">
                <div class="lex-overview-hero-body">
                    <div class="lex-overview-hero-header">
                        <div>
                            <p class="lex-overview-eyebrow">{{ districtEyebrow }}</p>
                            <h1 class="lex-overview-title">{{ heroTitle }}</h1>
                            <p v-if="stats?.district.address" class="mt-3 text-sm" style="color: rgba(255,255,255,0.68)">
                                {{ stats.district.address }}
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
                <section class="lex-dashboard-main-grid">
                    <div class="space-y-6">
                        <SectionPanel :title="t('district.recent_cases')">
                            <DataTable
                                :columns="caseColumns"
                                :rows="recentCases"
                                row-key="debt_ulid"
                                :paginate="false"
                            >
                                <template #cell-date="{ row }">
                                    <RouterLink
                                        :to="{ name: 'debt', params: { district: districtUlid, debtor: row.debtor_ulid, debt: row.debt_ulid } }"
                                        class="font-medium hover:underline"
                                        style="color: var(--lex-accent)"
                                    >
                                        {{ row.date ? formatDate(row.date) : t('district.none') }}
                                    </RouterLink>
                                </template>
                                <template #cell-debtor_name="{ row }">
                                    <RouterLink
                                        :to="{ name: 'debtor', params: { district: districtUlid, debtor: row.debtor_ulid } }"
                                        class="font-medium hover:underline"
                                        style="color: var(--lex-accent)"
                                    >
                                        {{ row.debtor_name || t('district.none') }}
                                    </RouterLink>
                                </template>
                                <template #cell-case_number="{ row }">
                                    <RouterLink
                                        :to="{ name: 'debt', params: { district: districtUlid, debtor: row.debtor_ulid, debt: row.debt_ulid } }"
                                        class="font-medium hover:underline"
                                        style="color: var(--lex-accent)"
                                    >
                                        {{ row.case_number || t('district.none') }}
                                    </RouterLink>
                                </template>
                                <template #cell-amount="{ row }">
                                    <RouterLink
                                        :to="{ name: 'debt', params: { district: districtUlid, debtor: row.debtor_ulid, debt: row.debt_ulid } }"
                                        class="font-medium hover:underline"
                                        style="color: var(--lex-accent)"
                                    >
                                        {{ formatAmount(row.amount) }}
                                    </RouterLink>
                                </template>
                            </DataTable>
                        </SectionPanel>

                        <SectionPanel :title="t('district.recent_debtors')">
                            <DataTable
                                :columns="debtorColumns"
                                :rows="recentDebtors"
                                row-key="ulid"
                                :paginate="false"
                            >
                                <template #cell-created_at="{ row }">
                                    <RouterLink
                                        :to="{ name: 'debtor', params: { district: districtUlid, debtor: row.ulid } }"
                                        class="font-medium hover:underline"
                                        style="color: var(--lex-accent)"
                                    >
                                        {{ row.created_at ? formatDate(row.created_at) : t('district.none') }}
                                    </RouterLink>
                                </template>
                                <template #cell-name="{ row }">
                                    <RouterLink
                                        :to="{ name: 'debtor', params: { district: districtUlid, debtor: row.ulid } }"
                                        class="font-medium hover:underline"
                                        style="color: var(--lex-accent)"
                                    >
                                        {{ debtorDisplayName(row) }}
                                    </RouterLink>
                                </template>
                                <template #cell-case_number="{ row }">
                                    <RouterLink
                                        :to="{ name: 'debtor', params: { district: districtUlid, debtor: row.ulid } }"
                                        class="font-medium hover:underline"
                                        style="color: var(--lex-accent)"
                                    >
                                        {{ row.case_number || t('district.none') }}
                                    </RouterLink>
                                </template>
                            </DataTable>
                        </SectionPanel>

                        <SectionPanel
                            :title="t('district.recent_payments')"
                            :action-label="t('district.all_payments')"
                            :action-to="{ name: 'district-payments', params: { district: districtUlid } }"
                        >
                            <DataTable
                                :columns="paymentColumns"
                                :rows="recentPayments"
                                row-key="ulid"
                                :paginate="false"
                            >
                                <template #cell-date="{ row }">
                                    <RouterLink
                                        v-if="row.ulid && row.debt_ulid && row.debtor_ulid"
                                        :to="{ name: 'payment-show', params: { district: districtUlid, debtor: row.debtor_ulid, debt: row.debt_ulid, payment: row.ulid } }"
                                        class="font-medium hover:underline"
                                        style="color: var(--lex-accent)"
                                    >
                                        {{ row.date ? formatDate(row.date) : t('district.none') }}
                                    </RouterLink>
                                    <span v-else>{{ row.date ? formatDate(row.date) : t('district.none') }}</span>
                                </template>
                                <template #cell-debtor_name="{ row }">
                                    <RouterLink
                                        v-if="row.debtor_ulid"
                                        :to="{ name: 'debtor', params: { district: districtUlid, debtor: row.debtor_ulid } }"
                                        class="font-medium hover:underline"
                                        style="color: var(--lex-accent)"
                                    >
                                        {{ row.debtor_name || t('district.none') }}
                                    </RouterLink>
                                    <span v-else>{{ t('district.none') }}</span>
                                </template>
                                <template #cell-amount="{ row }">
                                    <RouterLink
                                        v-if="row.debt_ulid && row.debtor_ulid"
                                        :to="{ name: 'debt', params: { district: districtUlid, debtor: row.debtor_ulid, debt: row.debt_ulid } }"
                                        class="font-medium hover:underline"
                                        style="color: var(--lex-accent)"
                                    >
                                        {{ formatAmount(row.amount) }}
                                    </RouterLink>
                                    <span v-else>{{ formatAmount(row.amount) }}</span>
                                </template>
                                <template #cell-case_number="{ row }">
                                    <RouterLink
                                        v-if="row.debt_ulid && row.debtor_ulid"
                                        :to="{ name: 'debt', params: { district: districtUlid, debtor: row.debtor_ulid, debt: row.debt_ulid } }"
                                        class="font-medium hover:underline"
                                        style="color: var(--lex-accent)"
                                    >
                                        {{ row.case_number || t('district.none') }}
                                    </RouterLink>
                                    <span v-else>{{ t('district.none') }}</span>
                                </template>
                            </DataTable>
                        </SectionPanel>
                    </div>

                    <SectionPanel tag="aside" :title="t('dashboard.quick_actions_title')">
                        <div class="lex-dashboard-actions-stack">
                            <AppButton
                                :to="{ name: 'debtors', params: { district: districtUlid } }"
                                icon="ri-login-circle-line"
                                full
                            >
                                {{ t('district.open_debtors') }}
                            </AppButton>

                            <AppButton
                                v-if="stats.can_create_payment"
                                :to="{ name: 'operation-create-payment', params: { district: districtUlid } }"
                                icon="ri-cash-line"
                                full
                            >
                                {{ t('dashboard.add_payment') }}
                            </AppButton>

                            <AppButton
                                v-if="stats.can_create_debt"
                                :to="{ name: 'debt-create', params: { district: districtUlid } }"
                                icon="ri-file-add-line"
                                full
                            >
                                {{ t('dashboard.add_debt') }}
                            </AppButton>

                            <AppButton
                                v-if="stats.can_create_debtor"
                                :to="{ name: 'debtor-create', params: { district: districtUlid } }"
                                icon="ri-user-add-line"
                                full
                            >
                                {{ t('dashboard.add_debtor') }}
                            </AppButton>

                            <AppButton
                                v-if="stats.can_manage_users"
                                :to="{ name: 'user-management', params: { district: districtUlid } }"
                                variant="primary"
                                icon="ri-team-line"
                                full
                            >
                                {{ t('dashboard.manage_users') }}
                            </AppButton>
                        </div>
                    </SectionPanel>
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
import AppButton from '@/components/ui/AppButton.vue';
import DataTable from '@/components/ui/DataTable.vue';
import type { TableColumn } from '@/components/ui/DataTable.vue';
import SectionPanel from '@/components/ui/SectionPanel.vue';
import {
    fetchDistrictStats,
    fetchDistrictRecentPayments,
    type DistrictStats,
    type DistrictRecentPayment,
} from '@/api/districts';
import { fetchDistrictDebtCases, type DebtCase } from '@/api/debts';
import { listDebtors, debtorDisplayName, type Debtor } from '@/api/debtors';
import { useBreadcrumbStore } from '@/stores/breadcrumb';
import { useUserFormatting } from '@/composables/useUserFormatting';

const { t } = useI18n();
const route = useRoute();
const breadcrumbStore = useBreadcrumbStore();
const { formatDate, formatAmount } = useUserFormatting();

const districtUlid = computed(() => String(route.params.district ?? ''));
const loading = ref(true);
const loadError = ref(false);
const stats = ref<DistrictStats | null>(null);
const recentCases = ref<DebtCase[]>([]);
const recentDebtors = ref<Debtor[]>([]);
const recentPayments = ref<DistrictRecentPayment[]>([]);

const districtEyebrow = computed(() => {
    if (!stats.value) return t('district.eyebrow');
    const { number, court } = stats.value.district;
    const label = t('district.number_label', { number });
    return court ? `${label} · ${court}` : label;
});

const heroTitle = computed(() => {
    if (!stats.value) return t('district.eyebrow');
    const { bailiff_name, bailiff_surname } = stats.value.district;
    const name = [bailiff_name, bailiff_surname].filter(Boolean).join(' ');
    return name || t('district.default_title', { number: stats.value.district.number });
});

const caseColumns = computed((): TableColumn<DebtCase>[] => [
    { key: 'date', label: t('district.col_date'), thClass: 'w-[1%] whitespace-nowrap' },
    { key: 'debtor_name', label: t('district.col_debtor') },
    { key: 'case_number', label: t('district.col_case'), align: 'right' },
    { key: 'amount', label: t('district.col_amount'), align: 'right' },
]);

const debtorColumns = computed((): TableColumn<Debtor>[] => [
    { key: 'created_at', label: t('district.col_date'), thClass: 'w-[1%] whitespace-nowrap' },
    { key: 'name', label: t('district.col_debtor') },
    { key: 'case_number', label: t('district.col_latest_case'), align: 'right' },
]);

const paymentColumns = computed((): TableColumn<DistrictRecentPayment>[] => [
    { key: 'date', label: t('district.col_date'), thClass: 'w-[1%] whitespace-nowrap' },
    { key: 'debtor_name', label: t('district.col_debtor') },
    { key: 'case_number', label: t('district.col_case'), align: 'right' },
    { key: 'amount', label: t('district.col_amount'), align: 'right' },
]);

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = false;

    try {
        const [statsData, casesData, debtorsData, paymentsData] = await Promise.all([
            fetchDistrictStats(districtUlid.value),
            fetchDistrictDebtCases(districtUlid.value),
            listDebtors(districtUlid.value, { per_page: 5 }),
            fetchDistrictRecentPayments(districtUlid.value, 5).catch(() => [] as DistrictRecentPayment[]),
        ]);

        stats.value = statsData;
        recentCases.value = casesData;
        recentDebtors.value = debtorsData.data;
        recentPayments.value = paymentsData;
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
