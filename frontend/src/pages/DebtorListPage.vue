<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-panel lex-panel-header p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <p class="lex-page-eyebrow">{{ t('debtors.eyebrow') }}</p>
                        <h1 class="lex-page-title">{{ t('debtors.title') }}</h1>
                    </div>

                    <RouterLink
                        v-if="stats?.can_create_debtor"
                        :to="{ name: 'debtor-create', params: { district: districtUlid } }"
                        class="lex-button lex-button-primary"
                    >
                        {{ t('debtors.add') }}
                    </RouterLink>
                </div>
            </section>

            <section class="lex-panel p-8">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <label class="lex-form-field">
                        <span class="lex-input-label">{{ t('debtors.search_label') }}</span>
                        <input
                            v-model="searchInput"
                            type="search"
                            class="lex-input"
                            :placeholder="t('debtors.search_placeholder')"
                            @input="onSearchInput"
                        />
                    </label>

                    <label class="lex-form-field">
                        <span class="lex-input-label">{{ t('debtors.type_filter') }}</span>
                        <select v-model="typeFilter" class="lex-input" @change="onFilterChange">
                            <option value="">{{ t('debtors.type_all') }}</option>
                            <option value="physical">{{ t('operations.type_physical') }}</option>
                            <option value="legal">{{ t('operations.type_legal') }}</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="lex-panel p-8">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-slate-500">
                        {{ t('debtors.total_count', { count: meta.total }) }}
                    </p>
                    <p v-if="loadError !== ''" class="lex-form-message lex-form-message-error">{{ loadError }}</p>
                </div>

                <div v-if="loading" class="py-8 text-center text-sm text-slate-500">
                    {{ t('debtors.loading') }}
                </div>

                <template v-else>
                    <div v-if="debtors.length === 0" class="lex-dashboard-empty">
                        {{ searchInput || typeFilter ? t('debtors.empty_filtered') : t('debtors.empty') }}
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-0">
                            <thead>
                                <tr class="text-left">
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('debtors.columns.case_number') }}
                                    </th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('debtors.columns.type') }}
                                    </th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('debtors.columns.name') }}
                                    </th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('debtors.columns.identifier') }}
                                    </th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('debtors.columns.phone') }}
                                    </th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('debtors.columns.actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="debtor in debtors"
                                    :key="debtor.ulid"
                                    class="hover:bg-slate-50"
                                    :class="{ 'opacity-50': debtor.is_deleted }"
                                >
                                    <td class="border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-800">
                                        {{ debtor.case_number || t('debtors.none') }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-sm text-slate-600">
                                        {{ debtor.type === 'legal' ? t('operations.type_legal') : t('operations.type_physical') }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-sm text-slate-800">
                                        {{ debtorDisplayName(debtor) }}
                                        <span
                                            v-if="debtor.is_deleted"
                                            class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500"
                                        >
                                            {{ t('debtors.deleted_badge') }}
                                        </span>
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-sm text-slate-600">
                                        {{ debtorIdentifier(debtor) }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-sm text-slate-600">
                                        {{ debtor.phone || t('debtors.none') }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3">
                                        <RouterLink
                                            :to="{ name: 'debtor', params: { district: districtUlid, debtor: debtor.ulid } }"
                                            class="lex-button lex-button-secondary text-xs"
                                        >
                                            {{ t('debtors.view') }}
                                        </RouterLink>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="meta.last_page > 1" class="mt-6 flex items-center justify-between">
                        <p class="text-sm text-slate-500">
                            {{ t('debtors.page_info', { current: meta.current_page, total: meta.last_page }) }}
                        </p>
                        <div class="flex gap-2">
                            <RouterLink
                                :to="pageLink(meta.current_page - 1)"
                                class="lex-button lex-button-secondary text-sm"
                                :class="{ 'pointer-events-none opacity-40': meta.current_page <= 1 }"
                            >
                                {{ t('debtors.previous') }}
                            </RouterLink>
                            <RouterLink
                                :to="pageLink(meta.current_page + 1)"
                                class="lex-button lex-button-secondary text-sm"
                                :class="{ 'pointer-events-none opacity-40': meta.current_page >= meta.last_page }"
                            >
                                {{ t('debtors.next') }}
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
import { listDebtors, debtorDisplayName, type Debtor, type DebtorListMeta } from '@/api/debtors';
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
const debtors = ref<Debtor[]>([]);
const meta = ref<DebtorListMeta>({ current_page: 1, last_page: 1, total: 0, per_page: 25 });
const stats = ref<DistrictStats | null>(null);
const typeFilter = ref(String(route.query.type ?? ''));

const { defineField } = useForm<{ search: string }>({
    validationSchema: toTypedSchema(z.object({ search: z.string().optional() })),
    initialValues: { search: String(route.query.search ?? '') },
});

const [searchInput] = defineField('search');

let searchDebounce: ReturnType<typeof setTimeout> | null = null;

function debtorIdentifier(debtor: Debtor): string {
    if (debtor.type === 'legal') {
        return debtor.registration_number || t('debtors.none');
    }

    return debtor.personal_code || t('debtors.none');
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

    return { name: 'debtors', params: { district: districtUlid.value }, query };
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
            listDebtors(districtUlid.value, params),
            stats.value === null ? fetchDistrictStats(districtUlid.value) : Promise.resolve(stats.value),
        ]);

        debtors.value = response.data;
        meta.value = response.meta;

        if (districtStats) {
            stats.value = districtStats;
            breadcrumbStore.districtLabel = t('district.number_label', { number: districtStats.district.number });
        }
    } catch {
        loadError.value = t('debtors.load_error');
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
