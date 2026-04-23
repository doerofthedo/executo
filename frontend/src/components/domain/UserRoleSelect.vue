<template>
    <select :value="modelValue" class="lex-input" :disabled="disabled" @change="onChange">
        <option v-for="role in allowedRoles" :key="role" :value="role">
            {{ roleLabel(role) }}
        </option>
    </select>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import type { DistrictUserRole } from '@/api/users';

const props = withDefaults(defineProps<{
    modelValue: DistrictUserRole;
    allowedRoles?: DistrictUserRole[];
    disabled?: boolean;
}>(), {
    allowedRoles: () => ['district.admin', 'district.manager', 'district.user'],
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: DistrictUserRole];
}>();

const { t } = useI18n();

function roleLabel(role: DistrictUserRole): string {
    if (role === 'district.admin') {
        return t('operations.role_district_admin');
    }

    if (role === 'district.manager') {
        return t('operations.role_district_manager');
    }

    return t('operations.role_district_user');
}

function onChange(event: Event): void {
    const target = event.target;

    if (!(target instanceof HTMLSelectElement)) {
        return;
    }

    emit('update:modelValue', target.value as DistrictUserRole);
}
</script>
