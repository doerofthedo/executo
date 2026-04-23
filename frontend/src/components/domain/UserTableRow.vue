<template>
    <tr class="align-top">
        <td class="border-b border-slate-100 px-4 py-4">
            <div class="space-y-1">
                <p class="font-semibold text-slate-900">{{ displayName }}</p>
                <p v-if="user.is_owner" class="text-xs font-medium uppercase tracking-[0.16em] text-amber-700">
                    {{ t('user_management.owner_badge') }}
                </p>
            </div>
        </td>
        <td class="border-b border-slate-100 px-4 py-4 text-slate-700">{{ user.email ?? t('dashboard.not_set') }}</td>
        <td class="border-b border-slate-100 px-4 py-4">
            <div v-if="isEditing" class="flex flex-col gap-3">
                <UserRoleSelect v-model="editingRole" :allowed-roles="allowedRoles" :disabled="saving" />
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="lex-button lex-button-primary"
                        :disabled="saving"
                        @click="requestSaveRole"
                    >
                        {{ saving ? t('operations.saving') : t('user_management.save_role') }}
                    </button>
                    <button type="button" class="lex-button lex-button-secondary" @click="$emit('cancel-edit', user.ulid)">
                        {{ t('user_management.cancel') }}
                    </button>
                </div>
            </div>
            <span v-else class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">
                {{ roleLabel }}
            </span>
        </td>
        <td class="border-b border-slate-100 px-4 py-4">
            <span class="inline-flex rounded-full px-3 py-1 text-sm font-medium" :class="statusClass">
                {{ statusLabel }}
            </span>
        </td>
        <td class="border-b border-slate-100 px-4 py-4">
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="lex-button lex-button-secondary"
                    :disabled="!canEdit"
                    @click="$emit('start-edit', user)"
                >
                    {{ t('user_management.edit_role') }}
                </button>
                <button
                    type="button"
                    class="lex-button lex-button-secondary"
                    :disabled="!canRemove || removing"
                    @click="$emit('request-remove', user)"
                >
                    {{ removing ? t('user_management.removing') : t('user_management.remove_user') }}
                </button>
            </div>
        </td>
    </tr>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import type { DistrictUser, DistrictUserRole } from '@/api/users';
import UserRoleSelect from '@/components/domain/UserRoleSelect.vue';

const props = withDefaults(defineProps<{
    user: DistrictUser;
    editing: boolean;
    canEdit: boolean;
    canRemove: boolean;
    allowedRoles?: DistrictUserRole[];
    saving?: boolean;
    removing?: boolean;
}>(), {
    allowedRoles: () => ['district.admin', 'district.manager', 'district.user'],
    saving: false,
    removing: false,
});

const emit = defineEmits<{
    'start-edit': [user: DistrictUser];
    'cancel-edit': [userUlid: string];
    'request-save-role': [payload: { user: DistrictUser; role: DistrictUserRole }];
    'request-remove': [user: DistrictUser];
}>();

const { t } = useI18n();
const editingRole = ref<DistrictUserRole>(props.user.role ?? 'district.user');

const isEditing = computed(() => props.editing);
const displayName = computed(() => {
    const parts = [props.user.name, props.user.surname].filter((value): value is string => value !== null && value !== '');

    return parts.join(' ').trim() || t('dashboard.not_set');
});
const roleLabel = computed(() => {
    if (props.user.role === 'district.admin') {
        return t('operations.role_district_admin');
    }

    if (props.user.role === 'district.manager') {
        return t('operations.role_district_manager');
    }

    return t('operations.role_district_user');
});
const statusLabel = computed(() => {
    if (props.user.disabled) {
        return t('user_management.status_disabled');
    }

    if (!props.user.is_email_verified) {
        return t('user_management.status_pending');
    }

    return t('user_management.status_active');
});
const statusClass = computed(() => {
    if (props.user.disabled) {
        return 'bg-rose-100 text-rose-700';
    }

    if (!props.user.is_email_verified) {
        return 'bg-amber-100 text-amber-700';
    }

    return 'bg-emerald-100 text-emerald-700';
});

watch(() => props.editing, (editing) => {
    if (editing) {
        editingRole.value = props.user.role ?? 'district.user';
    }
});

watch(() => props.user.role, (role) => {
    editingRole.value = role ?? 'district.user';
});

function requestSaveRole(): void {
    emit('request-save-role', {
        user: props.user,
        role: editingRole.value,
    });
}
</script>
