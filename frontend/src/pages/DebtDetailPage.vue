<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-overview-hero-dark">
                <div class="lex-overview-hero-body">
                    <div class="lex-overview-hero-header">
                        <div>
                            <p class="lex-overview-eyebrow">{{ t('debt_detail.eyebrow') }}</p>
                            <h1 class="lex-overview-title">{{ heroTitle }}</h1>
                            <p v-if="detail?.debt.description" class="mt-2 text-sm text-slate-400">
                                {{ detail.debt.description }}
                            </p>
                        </div>

                        <div v-if="detail" class="lex-overview-metric-grid-dark">
                            <article class="lex-overview-metric-card-dark">
                                <p class="lex-overview-metric-label">{{ t('debt_detail.original_amount') }}</p>
                                <p class="lex-overview-metric-value">{{ formatAmount(detail.debt.amount) }}</p>
                            </article>
                            <article class="lex-overview-metric-card-dark">
                                <p class="lex-overview-metric-label">{{ t('debt_detail.payments_count') }}</p>
                                <p class="lex-overview-metric-value">{{ detail.payments.length }}</p>
                            </article>
                            <article v-if="totalRow" class="lex-overview-metric-card-dark">
                                <p class="lex-overview-metric-label">{{ t('debt_detail.total_debt') }}</p>
                                <p class="lex-overview-metric-value">{{ formatAmount(totalRow.total_debt) }}</p>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            <div v-if="loading" class="lex-panel p-8 text-sm text-slate-500">
                {{ t('debt_detail.loading') }}
            </div>

            <div v-else-if="loadError" class="lex-panel p-8">
                <p class="lex-form-message lex-form-message-error">{{ t('debt_detail.load_error') }}</p>
            </div>

            <template v-else-if="detail">
                <section class="lex-panel p-8">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <dl class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            <div v-if="totalRow" class="space-y-1">
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('debt_detail.remaining_principal') }}
                                </dt>
                                <dd class="text-sm text-slate-800">
                                    {{ formatAmount(totalRow.remaining_principal) }}
                                </dd>
                            </div>
                            <div v-if="totalRow" class="space-y-1">
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('debt_detail.remaining_interest') }}
                                </dt>
                                <dd class="text-sm text-slate-800">
                                    {{ formatAmount(totalRow.remaining_interest) }}
                                </dd>
                            </div>
                            <div class="space-y-1">
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('debt_detail.date') }}
                                </dt>
                                <dd class="text-sm text-slate-800">
                                    {{ detail.debt.date ? formatDate(detail.debt.date) : t('debt_detail.none') }}
                                </dd>
                            </div>
                            <div v-if="debtorName" class="space-y-1">
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('debt_detail.debtor') }}
                                </dt>
                                <dd class="text-sm text-slate-800">{{ debtorName }}</dd>
                            </div>
                        </dl>

                        <div class="flex flex-wrap gap-3">
                            <RouterLink
                                :to="{ name: 'payments', params: { district: districtUlid, debtor: debtorUlid, debt: debtUlid } }"
                                class="lex-button lex-button-primary"
                            >
                                {{ t('debt_detail.open_payments') }}
                            </RouterLink>
                            <RouterLink
                                :to="{ name: 'debtor', params: { district: districtUlid, debtor: debtorUlid } }"
                                class="lex-button lex-button-secondary"
                            >
                                {{ t('debt_detail.back') }}
                            </RouterLink>
                        </div>
                    </div>
                </section>

                <SectionPanel :title="t('debt_detail.breakdown_title')">
                    <DataTable
                        :columns="interestColumns"
                        :rows="detail.interest.rows"
                        :footer-row="detail.interest.total_row"
                        :row-class="interestRowClass"
                        :paginate="false"
                        compact
                    >
                        <template #cell-payment_amount="{ row, value }">
                            <RouterLink
                                v-if="row.payment_ulid"
                                :to="{ name: 'payments', params: { district: districtUlid, debtor: debtorUlid, debt: debtUlid }, hash: `#${row.payment_ulid}` }"
                                class="lex-data-table-link"
                            >
                                {{ formatCell('payment_amount', value as string | number | null) }}
                            </RouterLink>
                            <template v-else>{{ formatCell('payment_amount', value as string | number | null) }}</template>
                        </template>
                    </DataTable>
                </SectionPanel>
            </template>
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
import { fetchDebtDetail, type DebtDetail, type InterestRow } from '@/api/debts';
import { fetchDebtor, debtorDisplayName } from '@/api/debtors';
import { fetchDistrictStats } from '@/api/districts';
import { useBreadcrumbStore } from '@/stores/breadcrumb';
import { useUserFormatting } from '@/composables/useUserFormatting';

const AMOUNT_COLS = new Set([
    'payment_amount',
    'interest_accrued',
    'interest_rollover',
    'interest_total',
    'interest_paid',
    'principal_paid',
    'remaining_interest',
    'remaining_principal',
    'total_debt',
]);

const { t } = useI18n();
const route = useRoute();
const breadcrumbStore = useBreadcrumbStore();
const { formatDate, formatAmount } = useUserFormatting();

const districtUlid = computed(() => String(route.params.district ?? ''));
const debtorUlid = computed(() => String(route.params.debtor ?? ''));
const debtUlid = computed(() => String(route.params.debt ?? ''));

const loading = ref(true);
const loadError = ref(false);
const detail = ref<DebtDetail | null>(null);
const debtorName = ref<string | null>(null);
const caseNumber = ref<string | null>(null);

const totalRow = computed(() => detail.value?.interest.total_row ?? null);

const interestColumns = computed((): TableColumn<InterestRow>[] => {
    if (!detail.value) return [];
    return detail.value.interest.columns.map((col) => ({
        key: col.key,
        label: t(col.label_key),
        align: col.align,
        format: (value) => formatCell(col.key, value as string | number | null),
    }));
});

function interestRowClass(row: InterestRow, index: number, total: number): string {
    const rc = String(row.row_class ?? '');
    if (rc === 'table-danger' || rc === 'bg-red-100') return 'lex-data-table-row-danger';
    if (index === 0 || index === total - 1) return 'lex-data-table-row-muted';
    return '';
}

const heroTitle = computed(() => {
    if (!detail.value) {
        return t('debt_detail.eyebrow');
    }

    return caseNumber.value
        ? t('debt_detail.title', { number: caseNumber.value })
        : t('debt_detail.title_fallback');
});

function formatCell(key: string, value: string | number | null | undefined): string {
    if (value === null || value === undefined) {
        return t('debt_detail.none');
    }

    if (key === 'payment_date') {
        const str = String(value);

        if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
            return formatDate(str);
        }

        if (str === 'Total') {
            return t('debt_detail.breakdown_total');
        }

        return str || t('debt_detail.none');
    }

    if (key === 'interest_per_day') {
        return formatAmount(value, 6);
    }

    if (AMOUNT_COLS.has(key)) {
        return formatAmount(value, 2);
    }

    return String(value);
}

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = false;

    try {
        const [debtDetail, debtorData, statsData] = await Promise.all([
            fetchDebtDetail(districtUlid.value, debtorUlid.value, debtUlid.value),
            fetchDebtor(districtUlid.value, debtorUlid.value),
            fetchDistrictStats(districtUlid.value),
        ]);

        detail.value = debtDetail;
        debtorName.value = debtorDisplayName(debtorData);
        caseNumber.value = debtorData.case_number;

        const debtBreadcrumb = debtorData.case_number
            ? t('debt_detail.title', { number: debtorData.case_number })
            : t('debt_detail.title_fallback');

        breadcrumbStore.districtLabel = t('district.number_label', { number: statsData.district.number });
        breadcrumbStore.debtorLabel = debtorDisplayName(debtorData);
        breadcrumbStore.debtLabel = debtBreadcrumb;
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

onMounted(load);

onUnmounted(() => {
    breadcrumbStore.debtorLabel = null;
    breadcrumbStore.debtLabel = null;
    breadcrumbStore.districtLabel = null;
});
</script>
