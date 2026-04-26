<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-panel lex-panel-header p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <p class="lex-page-eyebrow">{{ t('customers.eyebrow') }}</p>
                        <h1 class="lex-page-title">{{ t('customers.title') }}</h1>
                    </div>

                    <RouterLink
                        v-if="stats?.can_create_customer"
                        :to="{ name: 'customer-create', params: { district: districtUlid } }"
                        class="lex-button lex-button-primary"
                    >
                        {{ t('customers.add') }}
                    </RouterLink>
                </div>
            </section>

            <section class="lex-panel p-8">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <label class="lex-form-field">
                        <span class="lex-input-label">{{ t('customers.search_label') }}</span>
                        <input
                            v-model="searchInput"
                            type="search"
                            class="lex-input"
                            :placeholder="t('customers.search_placeholder')"
                            @input="onSearchInput"
                        />
                    </label>

                    <label class="lex-form-field">
                        <span class="lex-input-label">{{ t('customers.type_filter') }}</span>
                        <select v-model="typeFilter" class="lex-input" @change="onFilterChange">
                            <option value="">{{ t('customers.type_all') }}</option>
                            <option value="physical">{{ t('operations.type_physical') }}</option>
                            <option value="legal">{{ t('operations.type_legal') }}</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="lex-panel p-8">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-slate-500">
                        {{ t('customers.total_count', { count: meta.total }) }}
                    </p>
                    <p v-if="loadError !== ''" class="lex-form-message lex-form-message-error">{{ loadError }}</p>
                </div>

                <div v-if="loading" class="py-8 text-center text-sm text-slate-500">
                    {{ t('customers.loading') }}
                </div>

                <template v-else>
                    <div v-if="customers.length === 0" class="lex-dashboard-empty">
                        {{ searchInput || typeFilter ? t('customers.empty_filtered') : t('customers.empty') }}
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-0">
                            <thead>
                                <tr class="text-left">
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('customers.columns.case_number') }}
                                    </th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('customers.columns.type') }}
                                    </th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('customers.columns.name') }}
                                    </th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('customers.columns.identifier') }}
                                    </th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('customers.columns.phone') }}
                                    </th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('customers.columns.actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="customer in customers"
                                    :key="customer.ulid"
                                    class="hover:bg-slate-50"
                                    :class="{ 'opacity-50': customer.is_deleted }"
                                >
                                    <td class="border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-800">
                                        {{ customer.case_number || t('customers.none') }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-sm text-slate-600">
                                        {{ customer.type === 'legal' ? t('operations.type_legal') : t('operations.type_physical') }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-sm text-slate-800">
                                        {{ customerDisplayName(customer) }}
                                        <span
                                            v-if="customer.is_deleted"
                                            class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500"
                                        >
                                            {{ t('customers.deleted_badge') }}
                                        </span>
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-sm text-slate-600">
                                        {{ customerIdentifier(customer) }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-sm text-slate-600">
                                        {{ customer.phone || t('customers.none') }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3">
                                        <RouterLink
                                            :to="{ name: 'customer', params: { district: districtUlid, customer: customer.ulid } }"
                                            class="lex-button lex-button-secondary text-xs"
                                        >
                                            {{ t('customers.view') }}
                                        </RouterLink>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="meta.last_page > 1" class="mt-6 flex items-center justify-between">
                        <p class="text-sm text-slate-500">
                            {{ t('customers.page_info', { current: meta.current_page, total: meta.last_page }) }}
                        </p>
                        <div class="flex gap-2">
                            <RouterLink
                                :to="pageLink(meta.current_page - 1)"
                                class="lex-button lex-button-secondary text-sm"
                                :class="{ 'pointer-events-none opacity-40': meta.current_page <= 1 }"
                            >
                                {{ t('customers.previous') }}
                            </RouterLink>
                            <RouterLink
                                :to="pageLink(meta.current_page + 1)"
                                class="lex-button lex-button-secondary text-sm"
                                :class="{ 'pointer-events-none opacity-40': meta.current_page >= meta.last_page }"
                            >
                                {{ t('customers.next') }}
                            </RouterLink>
                        </div>
                    </div>
                </template>
            </section>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { z } from 'zod';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { listCustomers, customerDisplayName, type Customer, type CustomerListMeta } from '@/api/customers';
import { fetchDistrictStats, type DistrictStats } from '@/api/districts';
import { useBreadcrumbStore } from '@/stores/breadcrumb';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const breadcrumbStore = useBreadcrumbStore();

const districtUlid = computed(() => String(route.params.district ?? ''));
const currentPage = computed(() => Number(route.query.page ?? 1));

const loading = ref(true);
const loadError = ref('');
const customers = ref<Customer[]>([]);
const meta = ref<CustomerListMeta>({ current_page: 1, last_page: 1, total: 0, per_page: 25 });
const stats = ref<DistrictStats | null>(null);
const typeFilter = ref(String(route.query.type ?? ''));

const { defineField } = useForm<{ search: string }>({
    validationSchema: toTypedSchema(z.object({ search: z.string().optional() })),
    initialValues: { search: String(route.query.search ?? '') },
});

const [searchInput] = defineField('search');

let searchDebounce: ReturnType<typeof setTimeout> | null = null;

function customerIdentifier(customer: Customer): string {
    if (customer.type === 'legal') {
        return customer.registration_number || t('customers.none');
    }

    return customer.personal_code || t('customers.none');
}

function pageLink(page: number): { name: string; params: { district: string }; query: Record<string, string> } {
    const query: Record<string, string> = {};

    if (page > 1) {
        query.page = String(page);
    }

    if (searchInput.value) {
        query.search = searchInput.value;
    }

    if (typeFilter.value) {
        query.type = typeFilter.value;
    }

    return { name: 'customers', params: { district: districtUlid.value }, query };
}

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = '';

    try {
        const params: Record<string, unknown> = {
            page: currentPage.value,
            per_page: 25,
        };

        if (searchInput.value) {
            params.search = searchInput.value;
        }

        if (typeFilter.value) {
            params.type = typeFilter.value;
        }

        const [response, districtStats] = await Promise.all([
            listCustomers(districtUlid.value, params),
            stats.value === null ? fetchDistrictStats(districtUlid.value) : Promise.resolve(stats.value),
        ]);

        customers.value = response.data;
        meta.value = response.meta;

        if (districtStats) {
            stats.value = districtStats;
            breadcrumbStore.districtLabel = t('district.number_label', { number: districtStats.district.number });
        }
    } catch {
        loadError.value = t('customers.load_error');
    } finally {
        loading.value = false;
    }
}

function onSearchInput(): void {
    if (searchDebounce !== null) {
        clearTimeout(searchDebounce);
    }

    searchDebounce = setTimeout(() => {
        void router.push(pageLink(1));
    }, 350);
}

function onFilterChange(): void {
    void router.push(pageLink(1));
}

watch(
    () => [currentPage.value, route.query.search, route.query.type] as const,
    ([, search, type]) => {
        searchInput.value = String(search ?? '');
        typeFilter.value = String(type ?? '');
        void load();
    },
);

onMounted(load);

onUnmounted(() => {
    if (searchDebounce !== null) {
        clearTimeout(searchDebounce);
    }

    breadcrumbStore.districtLabel = null;
});
</script>
