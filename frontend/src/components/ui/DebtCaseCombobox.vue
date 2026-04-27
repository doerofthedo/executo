<template>
    <div ref="root" class="lex-combobox" @keydown="onKeydown">
        <div class="lex-combobox-input-wrap" @click="open">
            <input
                ref="inputEl"
                v-model="query"
                type="text"
                class="lex-input lex-combobox-input"
                :placeholder="modelValue ? displayLabel(modelValue) : placeholder"
                :class="{ 'lex-combobox-input-selected': modelValue && !isOpen }"
                autocomplete="off"
                @focus="open"
                @input="isOpen = true"
            />
            <i class="ri-arrow-down-s-line lex-combobox-chevron" :class="{ 'lex-combobox-chevron-open': isOpen }" aria-hidden="true" />
        </div>

        <div v-if="isOpen && filtered.length > 0" class="lex-combobox-dropdown">
            <div class="lex-combobox-table-wrap">
                <table class="lex-combobox-table">
                    <thead>
                        <tr>
                            <th>{{ t('debt_case_combobox.debtor') }}</th>
                            <th>{{ t('debt_case_combobox.case_number') }}</th>
                            <th>{{ t('debt_case_combobox.date') }}</th>
                            <th>{{ t('debt_case_combobox.description') }}</th>
                            <th class="text-right">{{ t('debt_case_combobox.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, idx) in filtered"
                            :key="item.debt_ulid"
                            class="lex-combobox-option"
                            :class="{ 'lex-combobox-option-active': idx === activeIndex }"
                            @mousedown.prevent="select(item)"
                            @mousemove="activeIndex = idx"
                        >
                            <td>{{ item.debtor_name || t('debt_case_combobox.none') }}</td>
                            <td>{{ item.case_number || t('debt_case_combobox.none') }}</td>
                            <td>{{ item.date ? formatDate(item.date) : t('debt_case_combobox.none') }}</td>
                            <td>{{ item.description || t('debt_case_combobox.none') }}</td>
                            <td class="text-right">{{ formatAmount(item.amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-else-if="isOpen && query.length > 0" class="lex-combobox-empty">
            {{ t('debt_case_combobox.no_results') }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { onClickOutside } from '@vueuse/core';
import type { DebtCase } from '@/api/debts';
import { useUserFormatting } from '@/composables/useUserFormatting';

const props = withDefaults(defineProps<{
    modelValue: DebtCase | null;
    options: DebtCase[];
    placeholder?: string;
}>(), {
    modelValue: null,
    placeholder: '',
});

const emit = defineEmits<{
    'update:modelValue': [value: DebtCase | null];
}>();

const { t } = useI18n();
const { formatAmount, formatDate } = useUserFormatting();

const root = ref<HTMLElement | null>(null);
const inputEl = ref<HTMLInputElement | null>(null);
const isOpen = ref(false);
const query = ref('');
const activeIndex = ref(0);

function displayLabel(item: DebtCase): string {
    const parts = [item.debtor_name, item.case_number].filter(Boolean);
    return parts.length > 0 ? parts.join(' · ') : item.amount;
}

const filtered = computed(() => {
    const term = query.value.trim().toLowerCase();

    if (!term) return props.options;

    return props.options.filter((item) => {
        return (
            item.debtor_name?.toLowerCase().includes(term) ||
            item.case_number?.toLowerCase().includes(term) ||
            item.description?.toLowerCase().includes(term)
        );
    });
});

function open(): void {
    isOpen.value = true;
    activeIndex.value = 0;
}

function close(): void {
    isOpen.value = false;
    query.value = '';
}

function select(item: DebtCase): void {
    emit('update:modelValue', item);
    close();
}

function onKeydown(e: KeyboardEvent): void {
    if (!isOpen.value) {
        if (e.key === 'ArrowDown' || e.key === 'Enter') open();
        return;
    }

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        activeIndex.value = Math.min(activeIndex.value + 1, filtered.value.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        activeIndex.value = Math.max(activeIndex.value - 1, 0);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const item = filtered.value[activeIndex.value];
        if (item) select(item);
    } else if (e.key === 'Escape') {
        close();
    }
}

watch(query, () => { activeIndex.value = 0; });

onClickOutside(root, close);
</script>
