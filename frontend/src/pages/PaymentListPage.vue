<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-panel lex-panel-header p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <p class="lex-page-eyebrow">{{ t('payment_list.eyebrow') }}</p>
                        <h1 class="lex-page-title">{{ pageTitle }}</h1>
                        <p v-if="debtorName" class="lex-page-copy lex-page-copy-full text-sm">
                            {{ debtorName }}
                        </p>
                    </div>

                    <RouterLink
                        :to="{ name: 'debt', params: { district: districtUlid, debtor: debtorUlid, debt: debtUlid } }"
                        class="lex-button lex-button-secondary"
                    >
                        {{ t('payment_list.back') }}
                    </RouterLink>
                </div>
            </section>

            <div v-if="loading" class="lex-panel p-8 text-sm text-slate-500">
                {{ t('payment_list.loading') }}
            </div>

            <div v-else-if="loadError" class="lex-panel p-8">
                <p class="lex-form-message lex-form-message-error">{{ t('payment_list.load_error') }}</p>
            </div>

            <template v-else>
                <section v-if="canCreatePayment" class="lex-panel p-8">
                    <h2 class="mb-5 text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                        {{ mode === 'edit' ? t('payment_list.edit_title') : t('payment_list.create_title') }}
                    </h2>

                    <form class="lex-form" @submit.prevent="onSubmit">
                        <div class="lex-settings-grid">
                            <label class="lex-form-field">
                                <span class="lex-input-label">{{ t('payment_list.amount') }}</span>
                                <input
                                    v-model="formAmount"
                                    type="number"
                                    step="0.0001"
                                    min="0.0001"
                                    class="lex-input"
                                />
                                <p v-if="errors.amount" class="lex-input-error-message">{{ errors.amount }}</p>
                            </label>

                            <label class="lex-form-field">
                                <span class="lex-input-label">{{ t('payment_list.date') }}</span>
                                <input v-model="formDate" type="date" class="lex-input" />
                                <p v-if="errors.date" class="lex-input-error-message">{{ errors.date }}</p>
                            </label>

                            <label class="lex-form-field">
                                <span class="lex-input-label">{{ t('payment_list.description') }}</span>
                                <input v-model="formDescription" type="text" class="lex-input" />
                            </label>
                        </div>

                        <div class="lex-section-actions">
                            <button type="submit" class="lex-button lex-button-primary" :disabled="isSubmitting">
                                {{ isSubmitting ? t('payment_list.submitting') : (mode === 'edit' ? t('payment_list.save') : t('payment_list.submit')) }}
                            </button>
                            <button
                                v-if="mode === 'edit'"
                                type="button"
                                class="lex-button lex-button-secondary"
                                :disabled="isSubmitting"
                                @click="cancelEdit"
                            >
                                {{ t('payment_list.cancel') }}
                            </button>
                            <p v-if="formSuccess" class="lex-form-message lex-form-message-success">{{ formSuccess }}</p>
                            <p v-if="formError" class="lex-form-message lex-form-message-error">{{ formError }}</p>
                        </div>
                    </form>
                </section>

                <section class="lex-panel p-8">
                    <h2 class="mb-5 text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                        {{ t('payment_list.list_title') }}
                    </h2>

                    <div v-if="payments.length === 0" class="lex-dashboard-empty">
                        {{ t('payment_list.no_payments') }}
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-0">
                            <thead>
                                <tr class="text-left">
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('payment_list.columns.date') }}
                                    </th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-right text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('payment_list.columns.amount') }}
                                    </th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('payment_list.columns.description') }}
                                    </th>
                                    <th v-if="canCreatePayment" class="border-b border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ t('payment_list.columns.actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="payment in payments"
                                    :key="payment.ulid"
                                    class="hover:bg-slate-50"
                                    :class="{ 'bg-blue-50': editingUlid === payment.ulid }"
                                >
                                    <td class="border-b border-slate-100 px-4 py-3 text-sm text-slate-600">
                                        {{ payment.date ? formatDate(payment.date) : t('payment_list.none') }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-right text-sm font-medium text-slate-800">
                                        {{ formatAmount(payment.amount) }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-sm text-slate-600">
                                        {{ payment.description || t('payment_list.none') }}
                                    </td>
                                    <td v-if="canCreatePayment" class="border-b border-slate-100 px-4 py-3">
                                        <div class="flex gap-2">
                                            <button
                                                type="button"
                                                class="lex-button lex-button-secondary text-xs"
                                                @click="startEdit(payment)"
                                            >
                                                {{ t('payment_list.edit') }}
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                                                @click="requestDelete(payment)"
                                            >
                                                {{ t('payment_list.delete') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </template>
        </div>

        <ConfirmationDialog
            :open="pendingDeleteUlid !== null"
            :eyebrow="t('payment_list.confirm_delete_eyebrow')"
            :title="t('payment_list.confirm_delete_title')"
            :message="t('payment_list.confirm_delete_message')"
            :confirm-label="t('payment_list.confirm_delete')"
            :cancel-label="t('payment_list.cancel_label')"
            pending-label="..."
            :submitting="deleting"
            @cancel="pendingDeleteUlid = null"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { z } from 'zod';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import ConfirmationDialog from '@/components/ui/ConfirmationDialog.vue';
import {
    listPayments,
    createPayment,
    updatePayment,
    deletePayment,
    type Payment,
} from '@/api/payments';
import { fetchDebtor, debtorDisplayName } from '@/api/debtors';
import { fetchDebtDetail } from '@/api/debts';
import { fetchDistrictStats } from '@/api/districts';
import { useBreadcrumbStore } from '@/stores/breadcrumb';
import { useUserFormatting } from '@/composables/useUserFormatting';

const { t } = useI18n();
const route = useRoute();
const breadcrumbStore = useBreadcrumbStore();
const { formatDate, formatAmount } = useUserFormatting();

const districtUlid = computed(() => String(route.params.district ?? ''));
const debtorUlid = computed(() => String(route.params.debtor ?? ''));
const debtUlid = computed(() => String(route.params.debt ?? ''));

const loading = ref(true);
const loadError = ref(false);
const payments = ref<Payment[]>([]);
const debtorName = ref<string | null>(null);
const debtDescription = ref<string | null>(null);
const caseNumber = ref<string | null>(null);
const canCreatePayment = ref(false);

const mode = ref<'create' | 'edit'>('create');
const editingUlid = ref<string | null>(null);
const formSuccess = ref('');
const formError = ref('');

const paymentSchema = computed(() => toTypedSchema(z.object({
    amount: z.string().trim().min(1, t('auth.validation.field_required')),
    date: z.string().trim().min(1, t('auth.validation.field_required')),
    description: z.string().optional(),
})));

const {
    errors,
    defineField,
    handleSubmit,
    isSubmitting,
    setValues,
    resetForm,
} = useForm<{ amount: string; date: string; description: string | undefined }>({
    validationSchema: paymentSchema,
    initialValues: { amount: '', date: '', description: '' },
});

const [formAmount] = defineField('amount');
const [formDate] = defineField('date');
const [formDescription] = defineField('description');

const pendingDeleteUlid = ref<string | null>(null);
const deleting = ref(false);

const pageTitle = computed(() => {
    if (caseNumber.value) {
        return t('debt_detail.title', { number: caseNumber.value });
    }

    if (debtDescription.value) {
        return debtDescription.value;
    }

    return t('payment_list.eyebrow');
});

function sortPayments(list: Payment[]): Payment[] {
    return [...list].sort((a, b) => {
        if (a.date === b.date) {
            return a.ulid < b.ulid ? 1 : -1;
        }

        return (a.date ?? '') < (b.date ?? '') ? 1 : -1;
    });
}

function startEdit(payment: Payment): void {
    mode.value = 'edit';
    editingUlid.value = payment.ulid;
    setValues({ amount: payment.amount, date: payment.date ?? '', description: payment.description ?? '' });
    formSuccess.value = '';
    formError.value = '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cancelEdit(): void {
    mode.value = 'create';
    editingUlid.value = null;
    formSuccess.value = '';
    formError.value = '';
    resetForm();
}

const onSubmit = handleSubmit(async (values) => {
    formSuccess.value = '';
    formError.value = '';

    try {
        const input = {
            amount: values.amount,
            date: values.date,
            description: values.description || null,
        };

        if (mode.value === 'edit' && editingUlid.value !== null) {
            const updated = await updatePayment(districtUlid.value, debtorUlid.value, debtUlid.value, editingUlid.value, input);
            payments.value = sortPayments(payments.value.map((p) => (p.ulid === updated.ulid ? updated : p)));
            formSuccess.value = t('payment_list.updated');
            cancelEdit();
        } else {
            const created = await createPayment(districtUlid.value, debtorUlid.value, debtUlid.value, input);
            payments.value = sortPayments([created, ...payments.value]);
            formSuccess.value = t('payment_list.created');
            resetForm();
        }
    } catch {
        formError.value = mode.value === 'edit' ? t('payment_list.update_error') : t('payment_list.create_error');
    }
});

function requestDelete(payment: Payment): void {
    pendingDeleteUlid.value = payment.ulid;
}

async function confirmDelete(): Promise<void> {
    if (pendingDeleteUlid.value === null) {
        return;
    }

    deleting.value = true;

    try {
        await deletePayment(districtUlid.value, debtorUlid.value, debtUlid.value, pendingDeleteUlid.value);
        payments.value = payments.value.filter((p) => p.ulid !== pendingDeleteUlid.value);

        if (editingUlid.value === pendingDeleteUlid.value) {
            cancelEdit();
        }

        pendingDeleteUlid.value = null;
    } catch {
        pendingDeleteUlid.value = null;
    } finally {
        deleting.value = false;
    }
}

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = false;

    try {
        const [paymentList, debtorData, debtDetail, statsData] = await Promise.all([
            listPayments(districtUlid.value, debtorUlid.value, debtUlid.value),
            fetchDebtor(districtUlid.value, debtorUlid.value),
            fetchDebtDetail(districtUlid.value, debtorUlid.value, debtUlid.value),
            fetchDistrictStats(districtUlid.value),
        ]);

        payments.value = sortPayments(paymentList);
        debtorName.value = debtorDisplayName(debtorData);
        debtDescription.value = debtDetail.debt.description;
        caseNumber.value = debtorData.case_number;
        canCreatePayment.value = statsData.can_create_payment;

        const debtBreadcrumb = debtorData.case_number
            ? t('debt_detail.title', { number: debtorData.case_number })
            : debtDetail.debt.description || t('debt_detail.title_fallback');

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
