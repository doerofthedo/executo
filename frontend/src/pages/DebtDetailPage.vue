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
                                <dd class="font-mono text-sm text-slate-800">
                                    {{ formatAmount(totalRow.remaining_principal) }}
                                </dd>
                            </div>
                            <div v-if="totalRow" class="space-y-1">
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('debt_detail.remaining_interest') }}
                                </dt>
                                <dd class="font-mono text-sm text-slate-800">
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
                            <div v-if="customerName" class="space-y-1">
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('debt_detail.debtor') }}
                                </dt>
                                <dd class="text-sm text-slate-800">{{ customerName }}</dd>
                            </div>
                        </dl>

                        <div class="flex flex-wrap gap-3">
                            <RouterLink
                                :to="{ name: 'payments', params: { district: districtUlid, customer: customerUlid, debt: debtUlid } }"
                                class="lex-button lex-button-primary"
                            >
                                {{ t('debt_detail.open_payments') }}
                            </RouterLink>
                            <RouterLink
                                :to="{ name: 'customer', params: { district: districtUlid, customer: customerUlid } }"
                                class="lex-button lex-button-secondary"
                            >
                                {{ t('debt_detail.back') }}
                            </RouterLink>
                        </div>
                    </div>
                </section>

                <section class="lex-panel p-8">
                    <h2 class="mb-5 text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                        {{ t('debt_detail.breakdown_title') }}
                    </h2>

                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-0 text-xs">
                            <thead>
                                <tr>
                                    <th
                                        v-for="col in detail.interest.columns"
                                        :key="col.key"
                                        class="border-b border-slate-200 px-3 py-2.5 font-semibold uppercase tracking-[0.16em] text-slate-500"
                                        :class="col.align === 'right' ? 'text-right' : 'text-left'"
                                    >
                                        {{ t(col.label_key) }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, idx) in detail.interest.rows"
                                    :key="idx"
                                    :class="rowClass(row)"
                                >
                                    <td
                                        v-for="col in detail.interest.columns"
                                        :key="col.key"
                                        class="border-b border-slate-100 px-3 py-2 font-mono"
                                        :class="[col.align === 'right' ? 'text-right' : 'text-left', col.key === 'payment_date' ? 'font-sans' : '']"
                                    >
                                        {{ formatCell(col.key, row[col.key]) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="totalRow">
                                <tr class="bg-slate-800 font-semibold text-white">
                                    <td
                                        v-for="col in detail.interest.columns"
                                        :key="col.key"
                                        class="px-3 py-2.5 font-mono"
                                        :class="[col.align === 'right' ? 'text-right' : 'text-left', col.key === 'payment_date' ? 'font-sans' : '']"
                                    >
                                        {{ formatCell(col.key, totalRow[col.key]) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>
            </template>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { fetchDebtDetail, type DebtDetail, type InterestRow } from '@/api/debts';
import { fetchCustomer, customerDisplayName } from '@/api/customers';
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
const customerUlid = computed(() => String(route.params.customer ?? ''));
const debtUlid = computed(() => String(route.params.debt ?? ''));

const loading = ref(true);
const loadError = ref(false);
const detail = ref<DebtDetail | null>(null);
const customerName = ref<string | null>(null);
const caseNumber = ref<string | null>(null);

const totalRow = computed(() => detail.value?.interest.total_row ?? null);

const heroTitle = computed(() => {
    if (!detail.value) {
        return t('debt_detail.eyebrow');
    }

    return caseNumber.value
        ? t('debt_detail.title', { number: caseNumber.value })
        : t('debt_detail.title_fallback');
});

function rowClass(row: InterestRow): string {
    const rc = String(row.row_class ?? '');

    if (rc === 'table-danger' || rc === 'bg-red-100') {
        return 'bg-red-50';
    }

    return '';
}

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
        const [debtDetail, customerData, statsData] = await Promise.all([
            fetchDebtDetail(districtUlid.value, customerUlid.value, debtUlid.value),
            fetchCustomer(districtUlid.value, customerUlid.value),
            fetchDistrictStats(districtUlid.value),
        ]);

        detail.value = debtDetail;
        customerName.value = customerDisplayName(customerData);
        caseNumber.value = customerData.case_number;

        const debtBreadcrumb = customerData.case_number
            ? t('debt_detail.title', { number: customerData.case_number })
            : t('debt_detail.title_fallback');

        breadcrumbStore.districtLabel = t('district.number_label', { number: statsData.district.number });
        breadcrumbStore.customerLabel = customerDisplayName(customerData);
        breadcrumbStore.debtLabel = debtBreadcrumb;
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

onMounted(load);

onUnmounted(() => {
    breadcrumbStore.customerLabel = null;
    breadcrumbStore.debtLabel = null;
    breadcrumbStore.districtLabel = null;
});
</script>
