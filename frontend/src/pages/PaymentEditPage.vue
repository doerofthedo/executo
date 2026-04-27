<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-panel lex-panel-header p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <p class="lex-page-eyebrow">{{ t('payment.eyebrow') }}</p>
                        <h1 class="lex-page-title">{{ t('payment_list.edit_title') }}</h1>
                    </div>

                    <RouterLink
                        :to="{ name: 'payment-show', params: { district: districtUlid, debtor: debtorUlid, debt: debtUlid, payment: paymentUlid } }"
                        class="lex-button lex-button-secondary"
                    >
                        {{ t('payment.back') }}
                    </RouterLink>
                </div>
            </section>

            <div v-if="loading" class="lex-panel p-8 text-sm text-slate-500">
                {{ t('payment.loading') }}
            </div>

            <div v-else-if="loadError" class="lex-panel p-8">
                <p class="lex-form-message lex-form-message-error">{{ t('payment.load_error') }}</p>
            </div>

            <template v-else>
                <SectionPanel :title="t('payment_list.edit_title')">
                    <form class="lex-form" @submit.prevent="onSubmit">
                        <div class="lex-settings-grid">
                            <label class="lex-form-field">
                                <span class="lex-input-label">{{ t('payment.amount') }}</span>
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
                                <span class="lex-input-label">{{ t('payment.date') }}</span>
                                <input v-model="formDate" type="date" class="lex-input" />
                                <p v-if="errors.date" class="lex-input-error-message">{{ errors.date }}</p>
                            </label>

                            <label class="lex-form-field">
                                <span class="lex-input-label">{{ t('payment.description') }}</span>
                                <input v-model="formDescription" type="text" class="lex-input" />
                            </label>
                        </div>

                        <div class="lex-section-actions">
                            <button type="submit" class="lex-button lex-button-primary" :disabled="isSubmitting">
                                {{ isSubmitting ? t('payment.submitting') : t('payment.save') }}
                            </button>
                            <RouterLink
                                :to="{ name: 'payment-show', params: { district: districtUlid, debtor: debtorUlid, debt: debtUlid, payment: paymentUlid } }"
                                class="lex-button lex-button-secondary"
                            >
                                {{ t('payment.cancel') }}
                            </RouterLink>
                            <p v-if="formError" class="lex-form-message lex-form-message-error">{{ formError }}</p>
                        </div>
                    </form>
                </SectionPanel>
            </template>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { z } from 'zod';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import SectionPanel from '@/components/ui/SectionPanel.vue';
import { fetchPayment, updatePayment } from '@/api/payments';
import { fetchDebtor, debtorDisplayName } from '@/api/debtors';
import { fetchDebtDetail } from '@/api/debts';
import { fetchDistrictStats } from '@/api/districts';
import { useBreadcrumbStore } from '@/stores/breadcrumb';
import { useUserFormatting } from '@/composables/useUserFormatting';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const breadcrumbStore = useBreadcrumbStore();
const { formatAmount } = useUserFormatting();

const districtUlid = computed(() => String(route.params.district ?? ''));
const debtorUlid = computed(() => String(route.params.debtor ?? ''));
const debtUlid = computed(() => String(route.params.debt ?? ''));
const paymentUlid = computed(() => String(route.params.payment ?? ''));

const loading = ref(true);
const loadError = ref(false);
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
} = useForm<{ amount: string; date: string; description: string | undefined }>({
    validationSchema: paymentSchema,
    initialValues: { amount: '', date: '', description: '' },
});

const [formAmount] = defineField('amount');
const [formDate] = defineField('date');
const [formDescription] = defineField('description');

const onSubmit = handleSubmit(async (values) => {
    formError.value = '';

    try {
        await updatePayment(districtUlid.value, debtorUlid.value, debtUlid.value, paymentUlid.value, {
            amount: values.amount,
            date: values.date,
            description: values.description || null,
        });
        await router.push({ name: 'payment-show', params: { district: districtUlid.value, debtor: debtorUlid.value, debt: debtUlid.value, payment: paymentUlid.value } });
    } catch {
        formError.value = t('payment.update_error');
    }
});

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = false;

    try {
        const [paymentData, debtorData, debtDetail, statsData] = await Promise.all([
            fetchPayment(districtUlid.value, debtorUlid.value, debtUlid.value, paymentUlid.value),
            fetchDebtor(districtUlid.value, debtorUlid.value),
            fetchDebtDetail(districtUlid.value, debtorUlid.value, debtUlid.value),
            fetchDistrictStats(districtUlid.value),
        ]);

        setValues({ amount: paymentData.amount, date: paymentData.date ?? '', description: paymentData.description ?? '' });

        const debtBreadcrumb = debtorData.case_number
            ? t('debt_detail.title', { number: debtorData.case_number })
            : debtDetail.debt.description || t('debt_detail.title_fallback');

        breadcrumbStore.districtLabel = t('district.number_label', { number: statsData.district.number });
        breadcrumbStore.debtorLabel = debtorDisplayName(debtorData);
        breadcrumbStore.debtLabel = debtBreadcrumb;
        breadcrumbStore.paymentLabel = formatAmount(paymentData.amount);
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
    breadcrumbStore.paymentLabel = null;
});
</script>
