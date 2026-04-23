<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-panel lex-panel-header p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-3">
                        <p class="lex-page-eyebrow">{{ t('user_management.eyebrow') }}</p>
                        <h1 class="lex-page-title">{{ t('user_management.title') }}</h1>
                        <p class="lex-page-copy lex-page-copy-full">{{ t('user_management.intro') }}</p>
                        <p class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">
                            {{ accessModeLabel }}
                        </p>
                    </div>

                    <button
                        v-if="canManageUsers"
                        type="button"
                        class="lex-button lex-button-primary"
                        @click="openInviteModal"
                    >
                        {{ t('user_management.invite_user') }}
                    </button>
                </div>
            </section>

            <section class="lex-panel p-8">
                <div class="grid gap-4 md:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                    <label class="lex-form-field">
                        <span class="lex-input-label">{{ t('user_management.district') }}</span>
                        <select
                            v-if="showDistrictSelector"
                            :value="selectedDistrictUlid"
                            class="lex-input"
                            @change="onDistrictChange"
                        >
                            <option v-for="district in selectableDistricts" :key="district.district.ulid" :value="district.district.ulid">
                                {{ districtOptionLabel(district) }}
                            </option>
                        </select>
                        <div v-else class="lex-input flex min-h-12 items-center">
                            {{ currentDistrictName }}
                        </div>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="lex-form-field">
                            <span class="lex-input-label">{{ t('user_management.search_label') }}</span>
                            <input
                                v-model="searchTerm"
                                type="search"
                                class="lex-input"
                                :placeholder="t('user_management.search_placeholder')"
                            />
                        </label>

                        <label class="lex-form-field">
                            <span class="lex-input-label">{{ t('user_management.role_filter') }}</span>
                            <select v-model="roleFilter" class="lex-input">
                                <option value="all">{{ t('user_management.role_filter_all') }}</option>
                                <option value="district.admin">{{ t('operations.role_district_admin') }}</option>
                                <option value="district.manager">{{ t('operations.role_district_manager') }}</option>
                                <option value="district.user">{{ t('operations.role_district_user') }}</option>
                            </select>
                        </label>
                    </div>
                </div>
            </section>

            <section class="lex-panel p-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="lex-page-title text-[1.25rem]">{{ t('user_management.table_title') }}</h2>
                        <p class="lex-page-copy lex-page-copy-full">
                            {{ t('user_management.table_summary', { count: filteredUsers.length, district: currentDistrictName }) }}
                        </p>
                    </div>
                    <p v-if="flashMessage !== ''" class="rounded-full border border-emerald-300 bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700">
                        {{ flashMessage }}
                    </p>
                </div>

                <div v-if="loading" class="lex-dashboard-empty">
                    {{ t('user_management.loading') }}
                </div>

                <div v-else-if="loadError !== ''" class="lex-form-message lex-form-message-error mt-6">
                    {{ loadError }}
                </div>

                <div v-else-if="filteredUsers.length === 0" class="lex-dashboard-empty mt-6">
                    {{ emptyStateMessage }}
                </div>

                <div v-else class="mt-6 overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead>
                            <tr class="text-left">
                                <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('user_management.columns.name') }}
                                </th>
                                <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('user_management.columns.email') }}
                                </th>
                                <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('user_management.columns.role') }}
                                </th>
                                <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('user_management.columns.status') }}
                                </th>
                                <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ t('user_management.columns.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <UserTableRow
                                v-for="user in filteredUsers"
                                :key="user.ulid"
                                :user="user"
                                :editing="editingUserUlid === user.ulid"
                                :can-edit="canEditUser(user)"
                                :can-remove="canRemoveUser(user)"
                                :allowed-roles="allowedRoles"
                                :saving="savingRowUlid === user.ulid"
                                :removing="removingUserUlid === user.ulid"
                                @start-edit="startEditRole"
                                @cancel-edit="cancelEditRole"
                                @request-save-role="openRoleChangeConfirmation"
                                @request-remove="openRemoveConfirmation"
                            />
                        </tbody>
                    </table>
                </div>
            </section>

            <UserInviteModal
                :open="isInviteModalOpen"
                :district-name="currentDistrictName"
                :allowed-roles="allowedRoles"
                :submitting="inviteSubmitting"
                :submit-error="inviteError"
                @close="closeInviteModal"
                @submit="submitInvite"
            />

            <ConfirmationDialog
                :open="pendingRoleChange !== null"
                :eyebrow="t('user_management.confirm_role_change_eyebrow')"
                :title="t('user_management.confirm_role_change_title')"
                :message="pendingRoleChangeMessage"
                :confirm-label="t('user_management.confirm_role_change')"
                :cancel-label="t('user_management.cancel')"
                :pending-label="t('operations.saving')"
                :submitting="confirmingRoleChange"
                @cancel="closeRoleChangeConfirmation"
                @confirm="confirmRoleChange"
            />

            <ConfirmationDialog
                :open="pendingRemovalUser !== null"
                :eyebrow="t('user_management.confirm_remove_eyebrow')"
                :title="t('user_management.confirm_remove_title')"
                :message="pendingRemovalMessage"
                :confirm-label="t('user_management.confirm_remove')"
                :cancel-label="t('user_management.cancel')"
                :pending-label="t('user_management.removing')"
                :submitting="confirmingRemoval"
                @cancel="closeRemoveConfirmation"
                @confirm="confirmRemoveUser"
            />
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { getDashboardStats, type DashboardDistrictCard } from '@/api/dashboard';
import {
    getDistrictUsers,
    inviteDistrictUser,
    removeDistrictUser,
    updateDistrictUserRole,
    type DistrictUser,
    type DistrictUserFormInput,
    type DistrictUserRole,
} from '@/api/users';
import ConfirmationDialog from '@/components/ui/ConfirmationDialog.vue';
import UserInviteModal from '@/components/domain/UserInviteModal.vue';
import UserTableRow from '@/components/domain/UserTableRow.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const districtCards = ref<DashboardDistrictCard[]>([]);
const users = ref<DistrictUser[]>([]);
const loading = ref(true);
const loadError = ref('');
const searchTerm = ref('');
const roleFilter = ref<'all' | DistrictUserRole>('all');
const flashMessage = ref('');
const editingUserUlid = ref<string | null>(null);
const savingRowUlid = ref<string | null>(null);
const removingUserUlid = ref<string | null>(null);
const isInviteModalOpen = ref(false);
const inviteSubmitting = ref(false);
const inviteError = ref('');
const pendingRoleChange = ref<{ user: DistrictUser; role: DistrictUserRole } | null>(null);
const pendingRemovalUser = ref<DistrictUser | null>(null);
const confirmingRoleChange = ref(false);
const confirmingRemoval = ref(false);

const selectedDistrictUlid = computed(() => typeof route.params.district === 'string' ? route.params.district : '');
const isAppAdmin = computed(() => authStore.user?.is_app_admin === true);
const allowedRoles = computed<DistrictUserRole[]>(() => ['district.admin', 'district.manager', 'district.user']);
const manageableDistricts = computed(() => districtCards.value.filter((card) => card.can_manage_users));
const selectableDistricts = computed(() => isAppAdmin.value ? districtCards.value : manageableDistricts.value);
const showDistrictSelector = computed(() => isAppAdmin.value);
const currentDistrictCard = computed(() => selectableDistricts.value.find((card) => card.district.ulid === selectedDistrictUlid.value) ?? null);
const accessModeLabel = computed(() => {
    if (isAppAdmin.value) {
        return t('user_management.app_admin_scope');
    }

    return t('user_management.district_admin_scope');
});
const currentDistrictName = computed(() => {
    if (currentDistrictCard.value === null) {
        return t('dashboard.not_set');
    }

    const parts = [currentDistrictCard.value.district.bailiff_name, currentDistrictCard.value.district.bailiff_surname]
        .filter((part): part is string => part !== null && part !== '');

    return parts.length > 0
        ? `${districtLabel(currentDistrictCard.value)} — ${parts.join(' ')}`
        : districtLabel(currentDistrictCard.value);
});
const canManageUsers = computed(() => isAppAdmin.value || currentDistrictCard.value?.can_manage_users === true);
const normalizedSearch = computed(() => searchTerm.value.trim().toLowerCase());
const filteredUsers = computed(() => users.value.filter((user) => {
    const matchesRole = roleFilter.value === 'all' || user.role === roleFilter.value;
    const haystack = [user.name, user.surname, user.email]
        .filter((value): value is string => typeof value === 'string' && value !== '')
        .join(' ')
        .toLowerCase();
    const matchesSearch = normalizedSearch.value === '' || haystack.includes(normalizedSearch.value);

    return matchesRole && matchesSearch;
}));
const emptyStateMessage = computed(() => {
    if (users.value.length === 0) {
        return t('user_management.empty');
    }

    return t('user_management.empty_filtered');
});
const pendingRoleChangeMessage = computed(() => {
    if (pendingRoleChange.value === null) {
        return '';
    }

    return t('user_management.confirm_role_change_message', {
        user: userDisplayName(pendingRoleChange.value.user),
        role: roleLabel(pendingRoleChange.value.role),
    });
});
const pendingRemovalMessage = computed(() => {
    if (pendingRemovalUser.value === null) {
        return '';
    }

    return t('user_management.confirm_remove_message', {
        user: userDisplayName(pendingRemovalUser.value),
    });
});

function districtLabel(card: DashboardDistrictCard): string {
    return t('dashboard.district_label', { number: card.district.number });
}

function districtOptionLabel(card: DashboardDistrictCard): string {
    const parts = [card.district.bailiff_name, card.district.bailiff_surname].filter((part): part is string => part !== null && part !== '');

    return parts.length > 0 ? `${districtLabel(card)} — ${parts.join(' ')}` : districtLabel(card);
}

function userDisplayName(user: DistrictUser): string {
    const parts = [user.name, user.surname].filter((value): value is string => value !== null && value !== '');

    return parts.join(' ').trim() || t('dashboard.not_set');
}

function roleLabel(role: DistrictUserRole | null): string {
    if (role === 'district.admin') {
        return t('operations.role_district_admin');
    }

    if (role === 'district.manager') {
        return t('operations.role_district_manager');
    }

    return t('operations.role_district_user');
}

function startEditRole(user: DistrictUser): void {
    editingUserUlid.value = user.ulid;
}

function cancelEditRole(userUlid?: string): void {
    if (userUlid !== undefined && editingUserUlid.value !== userUlid) {
        return;
    }

    editingUserUlid.value = null;
}

function canEditUser(user: DistrictUser): boolean {
    return canManageUsers.value && !user.is_owner;
}

function canRemoveUser(user: DistrictUser): boolean {
    return canManageUsers.value && !user.is_owner;
}

function openInviteModal(): void {
    inviteError.value = '';
    isInviteModalOpen.value = true;
}

function closeInviteModal(): void {
    isInviteModalOpen.value = false;
}

async function loadDistrictCards(): Promise<void> {
    const stats = await getDashboardStats();

    districtCards.value = [...stats.data].sort((left, right) => left.district.number - right.district.number);

    const candidateDistricts = selectableDistricts.value;
    const selectedDistrictExists = candidateDistricts.some((card) => card.district.ulid === selectedDistrictUlid.value);

    if (candidateDistricts.length > 0 && (selectedDistrictUlid.value === '' || !selectedDistrictExists)) {
        await router.replace({ name: 'user-management', params: { district: candidateDistricts[0].district.ulid } });
    }
}

async function loadUsers(): Promise<void> {
    if (selectedDistrictUlid.value === '') {
        users.value = [];
        return;
    }

    loading.value = true;
    loadError.value = '';

    try {
        users.value = await getDistrictUsers(selectedDistrictUlid.value);
    } catch (error) {
        loadError.value = axios.isAxiosError(error) && error.response?.status === 403
            ? t('user_management.forbidden')
            : t('user_management.load_error');
        users.value = [];
    } finally {
        loading.value = false;
    }
}

async function refreshPageData(): Promise<void> {
    loading.value = true;
    loadError.value = '';

    try {
        await loadDistrictCards();
        await loadUsers();
    } catch {
        loadError.value = t('user_management.load_error');
        loading.value = false;
    }
}

async function onDistrictChange(event: Event): Promise<void> {
    const target = event.target;

    if (!(target instanceof HTMLSelectElement) || target.value === selectedDistrictUlid.value) {
        return;
    }

    await router.push({ name: 'user-management', params: { district: target.value } });
}

function openRoleChangeConfirmation(payload: { user: DistrictUser; role: DistrictUserRole }): void {
    if (payload.role === payload.user.role) {
        cancelEditRole(payload.user.ulid);
        return;
    }

    pendingRoleChange.value = payload;
}

function closeRoleChangeConfirmation(): void {
    pendingRoleChange.value = null;
}

async function confirmRoleChange(): Promise<void> {
    if (selectedDistrictUlid.value === '' || pendingRoleChange.value === null) {
        return;
    }

    confirmingRoleChange.value = true;
    savingRowUlid.value = pendingRoleChange.value.user.ulid;

    try {
        const updatedUser = await updateDistrictUserRole(
            selectedDistrictUlid.value,
            pendingRoleChange.value.user.ulid,
            pendingRoleChange.value.role,
        );
        users.value = users.value.map((entry) => entry.ulid === updatedUser.ulid ? updatedUser : entry);
        flashMessage.value = t('user_management.role_saved');
        cancelEditRole(updatedUser.ulid);
        closeRoleChangeConfirmation();
    } catch (error) {
        loadError.value = axios.isAxiosError(error) && typeof error.response?.data?.message === 'string'
            ? error.response.data.message
            : t('user_management.role_save_error');
    } finally {
        confirmingRoleChange.value = false;
        savingRowUlid.value = null;
    }
}

function openRemoveConfirmation(user: DistrictUser): void {
    pendingRemovalUser.value = user;
}

function closeRemoveConfirmation(): void {
    pendingRemovalUser.value = null;
}

async function confirmRemoveUser(): Promise<void> {
    if (selectedDistrictUlid.value === '') {
        return;
    }

    if (pendingRemovalUser.value === null) {
        return;
    }

    confirmingRemoval.value = true;
    removingUserUlid.value = pendingRemovalUser.value.ulid;

    try {
        await removeDistrictUser(selectedDistrictUlid.value, pendingRemovalUser.value.ulid);
        users.value = users.value.filter((entry) => entry.ulid !== pendingRemovalUser.value?.ulid);
        flashMessage.value = t('user_management.user_removed');
        closeRemoveConfirmation();
    } catch (error) {
        loadError.value = axios.isAxiosError(error) && typeof error.response?.data?.message === 'string'
            ? error.response.data.message
            : t('user_management.remove_error');
    } finally {
        confirmingRemoval.value = false;
        removingUserUlid.value = null;
    }
}

async function submitInvite(payload: DistrictUserFormInput): Promise<void> {
    inviteError.value = '';

    if (selectedDistrictUlid.value === '') {
        inviteError.value = t('user_management.select_district_first');
        return;
    }

    inviteSubmitting.value = true;

    try {
        const invitedUser = await inviteDistrictUser(selectedDistrictUlid.value, payload.email, payload.role);
        users.value = [...users.value, invitedUser].sort((left, right) => userDisplayName(left).localeCompare(userDisplayName(right)));
        flashMessage.value = t('user_management.invite_success');
        closeInviteModal();
    } catch (error) {
        inviteError.value = axios.isAxiosError(error) && typeof error.response?.data?.message === 'string'
            ? error.response.data.message
            : t('user_management.invite_error');
    } finally {
        inviteSubmitting.value = false;
    }
}

watch(() => route.params.district, async () => {
    flashMessage.value = '';
    cancelEditRole();
    closeRoleChangeConfirmation();
    closeRemoveConfirmation();
    await loadUsers();
});

onMounted(async () => {
    await refreshPageData();
});
</script>
