<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-overview-hero-dark">
                <div class="lex-overview-hero-body">
                    <div class="lex-overview-hero-header">
                        <div>
                            <p class="lex-overview-eyebrow">{{ t('customer_detail.eyebrow') }}</p>
                            <h1 class="lex-overview-title">{{ heroTitle }}</h1>
                            <p v-if="customer" class="mt-2 text-sm text-slate-400">
                                {{ customer.type === 'legal' ? t('operations.type_legal') : t('operations.type_physical') }}
                                <span v-if="customer.case_number"> · {{ t('customer_detail.case_number') }}: {{ customer.case_number }}</span>
                            </p>
                        </div>

                        <div v-if="customer" class="lex-overview-metric-grid-dark">
                            <article class="lex-overview-metric-card-dark">
                                <p class="lex-overview-metric-label">{{ t('customer_detail.stats_debts') }}</p>
                                <p class="lex-overview-metric-value">{{ debts.length }}</p>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            <div v-if="loading" class="lex-panel p-8 text-sm text-slate-500">
                {{ t('customer_detail.loading') }}
            </div>

            <div v-else-if="loadError" class="lex-panel p-8">
                <p class="lex-form-message lex-form-message-error">{{ t('customer_detail.load_error') }}</p>
            </div>

            <template v-else-if="customer">
                <section class="lex-panel p-8">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                            {{ t('customer_detail.identity_title') }}
                        </h2>
                        <RouterLink
                            :to="{ name: 'customers', params: { district: districtUlid } }"
                            class="lex-button lex-button-secondary text-sm"
                        >
                            {{ t('customer_detail.back') }}
                        </RouterLink>
                    </div>

                    <dl class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="space-y-1">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                {{ t('customer_detail.case_number') }}
                            </dt>
                            <dd class="text-sm text-slate-800">{{ customer.case_number || t('customer_detail.none') }}</dd>
                        </div>

                        <div class="space-y-1">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                {{ t('customer_detail.email') }}
                            </dt>
                            <dd class="text-sm text-slate-800">{{ customer.email || t('customer_detail.none') }}</dd>
                        </div>

                        <div class="space-y-1">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                {{ t('customer_detail.phone') }}
                            </dt>
                            <dd class="text-sm text-slate-800">{{ customer.phone || t('customer_detail.none') }}</dd>
                        </div>

                        <template v-if="customer.type === 'physical'">
                            <div class="space-y-1">
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('customer_detail.personal_code') }}
                                </dt>
                                <dd class="text-sm text-slate-800">{{ customer.personal_code || t('customer_detail.none') }}</dd>
                            </div>
                            <div class="space-y-1">
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('customer_detail.date_of_birth') }}
                                </dt>
                                <dd class="text-sm text-slate-800">
                                    {{ customer.date_of_birth ? formatDate(customer.date_of_birth) : t('customer_detail.none') }}
                                </dd>
                            </div>
                        </template>

                        <template v-if="customer.type === 'legal'">
                            <div class="space-y-1">
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('customer_detail.company_name') }}
                                </dt>
                                <dd class="text-sm text-slate-800">{{ customer.company_name || t('customer_detail.none') }}</dd>
                            </div>
                            <div class="space-y-1">
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('customer_detail.registration_number') }}
                                </dt>
                                <dd class="text-sm text-slate-800">{{ customer.registration_number || t('customer_detail.none') }}</dd>
                            </div>
                            <div class="space-y-1">
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('customer_detail.contact_person') }}
                                </dt>
                                <dd class="text-sm text-slate-800">{{ customer.contact_person || t('customer_detail.none') }}</dd>
                            </div>
                        </template>
                    </dl>
                </section>

                <section class="lex-panel p-8">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                            {{ t('customer_detail.debts_title') }}
                        </h2>
                        <RouterLink
                            v-if="districtStats?.can_create_debt"
                            :to="{ name: 'debt-create', params: { district: districtUlid } }"
                            class="lex-button lex-button-secondary text-sm"
                        >
                            {{ t('customer_detail.add_debt') }}
                        </RouterLink>
                    </div>

                    <div v-if="debts.length === 0" class="lex-dashboard-empty">
                        {{ t('customer_detail.no_debts') }}
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-0">
                            <thead>
                                <tr class="text-left">
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('customer_detail.debt_columns.description') }}
                                    </th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('customer_detail.debt_columns.date') }}
                                    </th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-right text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('customer_detail.debt_columns.amount') }}
                                    </th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('customer_detail.debt_columns.actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="debt in debts"
                                    :key="debt.ulid"
                                    class="hover:bg-slate-50"
                                >
                                    <td class="border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-800">
                                        {{ debt.description || t('customer_detail.none') }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-sm text-slate-600">
                                        {{ debt.date ? formatDate(debt.date) : t('customer_detail.none') }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-right font-mono text-sm text-slate-800">
                                        {{ formatAmount(debt.amount) }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3">
                                        <div class="flex gap-2">
                                            <RouterLink
                                                :to="{ name: 'debt', params: { district: districtUlid, customer: customerUlid, debt: debt.ulid } }"
                                                class="lex-button lex-button-secondary text-xs"
                                            >
                                                {{ t('customer_detail.view_debt') }}
                                            </RouterLink>
                                            <RouterLink
                                                :to="{ name: 'payments', params: { district: districtUlid, customer: customerUlid, debt: debt.ulid } }"
                                                class="lex-button lex-button-inline text-xs"
                                            >
                                                {{ t('customer_detail.view_payments') }}
                                            </RouterLink>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
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
import { fetchCustomer, customerDisplayName, type Customer } from '@/api/customers';
import { listDebts, type Debt } from '@/api/debts';
import { fetchDistrictStats, type DistrictStats } from '@/api/districts';
import { useBreadcrumbStore } from '@/stores/breadcrumb';
import { useUserFormatting } from '@/composables/useUserFormatting';

const { t } = useI18n();
const route = useRoute();
const breadcrumbStore = useBreadcrumbStore();
const { formatDate, formatAmount } = useUserFormatting();

const districtUlid = computed(() => String(route.params.district ?? ''));
const customerUlid = computed(() => String(route.params.customer ?? ''));

const loading = ref(true);
const loadError = ref(false);
const customer = ref<Customer | null>(null);
const debts = ref<Debt[]>([]);
const districtStats = ref<DistrictStats | null>(null);

const heroTitle = computed(() => {
    if (!customer.value) {
        return t('customer_detail.eyebrow');
    }

    return customerDisplayName(customer.value);
});

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = false;

    try {
        const [customerData, debtsData, statsData] = await Promise.all([
            fetchCustomer(districtUlid.value, customerUlid.value),
            listDebts(districtUlid.value, customerUlid.value),
            fetchDistrictStats(districtUlid.value),
        ]);

        customer.value = customerData;
        debts.value = debtsData;
        districtStats.value = statsData;

        breadcrumbStore.districtLabel = t('district.number_label', { number: statsData.district.number });
        breadcrumbStore.customerLabel = customerDisplayName(customerData);
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

onMounted(load);

onUnmounted(() => {
    breadcrumbStore.customerLabel = null;
    breadcrumbStore.districtLabel = null;
});
</script>
