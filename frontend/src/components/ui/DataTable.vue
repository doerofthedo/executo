<template>
    <div class="lex-data-table-wrap" :class="{ 'lex-data-table--compact': compact }">
        <div v-if="hasFilters" class="lex-data-table-filters">
            <template v-for="col in columns" :key="String(col.key)">
                <label v-if="col.filterable" class="lex-data-table-filter-field">
                    <span class="lex-data-table-filter-label">{{ col.label }}</span>
                    <input
                        v-model="filters[String(col.key)]"
                        type="text"
                        class="lex-input lex-data-table-filter-input"
                        :placeholder="t('table.filter_placeholder')"
                        @input="currentPage = 1"
                    />
                </label>
            </template>
        </div>

        <div class="overflow-x-auto">
            <table class="lex-data-table">
                <thead>
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="String(col.key)"
                            class="lex-data-table-th"
                            :class="[
                                col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left',
                                col.sortable ? 'lex-data-table-th-sortable' : '',
                            ]"
                            @click="col.sortable ? toggleSort(String(col.key)) : undefined"
                        >
                            <span class="lex-data-table-th-inner">
                                {{ col.label }}
                                <span v-if="col.sortable" class="lex-data-table-sort-icon" aria-hidden="true">
                                    <i v-if="sortKey === String(col.key) && sortDir === 'asc'" class="ri-arrow-up-s-line" />
                                    <i v-else-if="sortKey === String(col.key) && sortDir === 'desc'" class="ri-arrow-down-s-line" />
                                    <i v-else class="ri-arrow-up-down-line lex-data-table-sort-inactive" />
                                </span>
                            </span>
                        </th>
                        <th v-if="hasActionsSlot" class="lex-data-table-th text-left" />
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="paginatedRows.length === 0">
                        <td
                            :colspan="columns.length + (hasActionsSlot ? 1 : 0)"
                            class="lex-data-table-empty"
                        >
                            <slot name="empty">{{ t('table.no_results') }}</slot>
                        </td>
                    </tr>

                    <tr
                        v-for="(row, idx) in paginatedRows"
                        :key="rowKey ? String(row[rowKey]) : String(idx)"
                        :id="rowId ? String(row[rowId]) : undefined"
                        class="lex-data-table-row"
                        :class="[
                            highlightRow?.(row) ? 'lex-data-table-row-highlight' : '',
                            rowClass?.(row, pageOffset + idx, totalRows) ?? '',
                        ]"
                    >
                        <td
                            v-for="col in columns"
                            :key="String(col.key)"
                            class="lex-data-table-td"
                            :class="col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : ''"
                        >
                            <slot :name="`cell-${String(col.key)}`" :row="row" :value="row[col.key]">
                                {{ col.format ? col.format(row[col.key], row) : String(row[col.key] ?? '') }}
                            </slot>
                        </td>
                        <td v-if="hasActionsSlot" class="lex-data-table-td lex-data-table-td-actions">
                            <slot name="actions" :row="row" />
                        </td>
                    </tr>
                </tbody>

                <tfoot v-if="footerRow">
                    <tr class="lex-data-table-footer-row">
                        <td
                            v-for="col in columns"
                            :key="String(col.key)"
                            class="lex-data-table-td"
                            :class="col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : ''"
                        >
                            {{ col.format ? col.format(footerRow[col.key], footerRow) : String(footerRow[col.key] ?? '') }}
                        </td>
                        <td v-if="hasActionsSlot" class="lex-data-table-td" />
                    </tr>
                </tfoot>
            </table>
        </div>

        <div v-if="paginate && totalRows > 0" class="lex-data-table-pagination">
            <span class="lex-data-table-page-info">
                {{ t('table.page_info', { from: pageFrom, to: pageTo, total: totalRows }) }}
            </span>
            <div class="lex-data-table-page-actions">
                <select
                    v-model="pageSize"
                    class="lex-input lex-data-table-page-size"
                    @change="currentPage = 1"
                >
                    <option v-for="size in PAGE_SIZE_OPTIONS" :key="size" :value="size">{{ size }}</option>
                </select>
                <button
                    type="button"
                    class="lex-button lex-button-secondary lex-data-table-page-btn"
                    :disabled="currentPage === 1"
                    @click="currentPage--"
                >
                    <i class="ri-arrow-left-s-line" aria-hidden="true" />
                </button>
                <button
                    type="button"
                    class="lex-button lex-button-secondary lex-data-table-page-btn"
                    :disabled="currentPage >= totalPages"
                    @click="currentPage++"
                >
                    <i class="ri-arrow-right-s-line" aria-hidden="true" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts" generic="T extends Record<string, unknown>">
import { computed, reactive, ref, useSlots, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePreferencesStore } from '@/stores/preferences';

export interface TableColumn<TRow> {
    key: keyof TRow & string;
    label: string;
    sortable?: boolean;
    filterable?: boolean;
    align?: 'left' | 'right' | 'center';
    format?: (value: unknown, row: TRow) => string;
}

const props = withDefaults(defineProps<{
    columns: TableColumn<T>[];
    rows: T[];
    rowKey?: keyof T & string;
    rowId?: keyof T & string;
    defaultPageSize?: number;
    highlightRow?: (row: T) => boolean;
    rowClass?: (row: T, index: number, total: number) => string;
    footerRow?: T | null;
    compact?: boolean;
    paginate?: boolean;
}>(), {
    rowKey: undefined,
    rowId: undefined,
    defaultPageSize: undefined,
    highlightRow: undefined,
    rowClass: undefined,
    footerRow: null,
    compact: false,
    paginate: true,
});

const { t } = useI18n();
const slots = useSlots();
const preferencesStore = usePreferencesStore();

const PAGE_SIZE_OPTIONS = [10, 25, 50, 100] as const;

const sortKey = ref<string | null>(null);
const sortDir = ref<'asc' | 'desc'>('asc');
const filters = reactive<Record<string, string>>({});
const currentPage = ref(1);
const pageSize = ref(props.defaultPageSize ?? preferencesStore.tablePageSize);

const hasFilters = computed(() => props.columns.some((c) => c.filterable));
const hasActionsSlot = computed(() => !!slots.actions);

const filteredRows = computed(() => {
    let result = props.rows as T[];

    for (const col of props.columns) {
        const term = (filters[col.key] ?? '').trim().toLowerCase();
        if (!term) continue;
        result = result.filter((row) => String(row[col.key] ?? '').toLowerCase().includes(term));
    }

    return result;
});

const sortedRows = computed(() => {
    if (!sortKey.value) return filteredRows.value;

    const key = sortKey.value;
    const dir = sortDir.value === 'asc' ? 1 : -1;

    return [...filteredRows.value].sort((a, b) => {
        const av = String(a[key] ?? '');
        const bv = String(b[key] ?? '');
        return av.localeCompare(bv, undefined, { numeric: true, sensitivity: 'base' }) * dir;
    });
});

const totalRows = computed(() => sortedRows.value.length);
const totalPages = computed(() => Math.max(1, Math.ceil(totalRows.value / pageSize.value)));
const pageOffset = computed(() => (currentPage.value - 1) * pageSize.value);
const pageFrom = computed(() => (totalRows.value === 0 ? 0 : pageOffset.value + 1));
const pageTo = computed(() => Math.min(currentPage.value * pageSize.value, totalRows.value));
const paginatedRows = computed(() => {
    if (!props.paginate) return sortedRows.value;
    return sortedRows.value.slice(pageOffset.value, pageOffset.value + pageSize.value);
});

function toggleSort(key: string): void {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'asc';
    }
    currentPage.value = 1;
}

watch(() => props.rows, () => { currentPage.value = 1; });
</script>
