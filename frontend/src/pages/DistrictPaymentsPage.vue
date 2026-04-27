<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-panel lex-panel-header p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <p class="lex-page-eyebrow">{{ districtEyebrow }}</p>
                        <h1 class="lex-page-title">{{ t('district.all_payments') }}</h1>
                    </div>

                    <RouterLink
                        :to="{ name: 'district', params: { district: districtUlid } }"
                        class="lex-button lex-button-secondary"
                    >
                        {{ t('district.back') }}
                    </RouterLink>
                </div>
            </section>

            <div v-if="loading" class="lex-panel p-8 text-sm text-slate-500">
                {{ t('district.loading') }}
            </div>

            <div v-else-if="loadError" class="lex-panel p-8">
                <p class="lex-form-message lex-form-message-error">{{ t('district.load_error') }}</p>
            </div>

            <SectionPanel v-else :title="t('district.payments_list_title')">
                <DataTable
                    :columns="columns"
                    :rows="payments"
                    row-key="ulid"
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
                </DataTable>
            </SectionPanel>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/ui/DataTable.vue';
import type { TableColumn } from '@/components/ui/DataTable.vue';
import SectionPanel from '@/components/ui/SectionPanel.vue';
import { fetchDistrictStats, fetchDistrictRecentPayments, type DistrictRecentPayment } from '@/api/districts';
import { useBreadcrumbStore } from '@/stores/breadcrumb';
import { useUserFormatting } from '@/composables/useUserFormatting';

const { t } = useI18n();
const route = useRoute();
const breadcrumbStore = useBreadcrumbStore();
const { formatDate, formatAmount } = useUserFormatting();

const districtUlid = computed(() => String(route.params.district ?? ''));
const loading = ref(true);
const loadError = ref(false);
const payments = ref<DistrictRecentPayment[]>([]);
const districtNumber = ref<number | null>(null);
const districtCourt = ref<string | null>(null);

const districtEyebrow = computed(() => {
    if (districtNumber.value === null) return t('district.eyebrow');
    const label = t('district.number_label', { number: districtNumber.value });
    return districtCourt.value ? `${label} · ${districtCourt.value}` : label;
});

const columns = computed((): TableColumn<DistrictRecentPayment>[] => [
    { key: 'date', label: t('district.col_date'), sortable: true, thClass: 'w-[1%] whitespace-nowrap' },
    { key: 'debtor_name', label: t('district.col_debtor'), sortable: true },
    { key: 'case_number', label: t('district.col_case'), align: 'right', sortable: true },
    { key: 'amount', label: t('district.col_amount'), align: 'right', sortable: true },
]);

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = false;

    try {
        const [statsData, paymentsData] = await Promise.all([
            fetchDistrictStats(districtUlid.value),
            fetchDistrictRecentPayments(districtUlid.value, 1000),
        ]);

        districtNumber.value = statsData.district.number;
        districtCourt.value = statsData.district.court;
        breadcrumbStore.districtLabel = t('district.number_label', { number: statsData.district.number });
        payments.value = paymentsData;
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

onMounted(load);

onUnmounted(() => {
    breadcrumbStore.districtLabel = null;
});
</script>
