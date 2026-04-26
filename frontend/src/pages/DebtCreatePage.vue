<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-panel lex-panel-header p-8">
                <p class="lex-page-eyebrow">{{ t('operations.debt_create_eyebrow') }}</p>
                <h1 class="lex-page-title">{{ t('operations.debt_create_title') }}</h1>
            </section>

            <section class="lex-panel p-8">
                <form class="lex-form" @submit.prevent="onSubmit">
                    <div class="lex-settings-grid">
                        <label class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.debtor') }}</span>
                            <select v-model="debtorUlidValue" class="lex-input">
                                <option value="">{{ t('operations.select_debtor') }}</option>
                                <option v-for="debtor in debtors" :key="debtor.ulid" :value="debtor.ulid">
                                    {{ debtor.name ?? debtor.case_number ?? debtor.ulid }}
                                </option>
                            </select>
                            <p v-if="errors.debtor_ulid" class="lex-input-error-message">{{ errors.debtor_ulid }}</p>
                        </label>

                        <label class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.amount') }}</span>
                            <input v-model="amountValue" class="lex-input" />
                            <p v-if="errors.amount" class="lex-input-error-message">{{ errors.amount }}</p>
                        </label>

                        <label class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.date') }}</span>
                            <input v-model="dateValue" type="date" class="lex-input" />
                            <p v-if="errors.date" class="lex-input-error-message">{{ errors.date }}</p>
                        </label>

                        <label class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.description') }}</span>
                            <textarea v-model="descriptionValue" class="lex-input min-h-28" />
                        </label>
                    </div>

                    <div class="lex-section-actions">
                        <button type="submit" class="lex-button lex-button-primary" :disabled="isSubmitting">
                            {{ isSubmitting ? t('operations.saving') : t('operations.create_debt') }}
                        </button>
                        <p v-if="formError !== ''" class="lex-form-message lex-form-message-error">{{ formError }}</p>
                    </div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { useForm } from 'vee-validate';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { createDebt, createDebtSchema, fetchDistrictDebtors, type DebtorOption, type DebtFormInput } from '@/api/operations';
import { isApiError } from '@/api/client';
import { toTypedSchema } from '@vee-validate/zod';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();
const formError = ref('');
const debtors = ref<DebtorOption[]>([]);
const defaultDistrictUlid = computed(() => typeof route.params.district === 'string'
    ? route.params.district
    : (authStore.user?.default_district_ulid ?? null));
const schema = computed(() => toTypedSchema(createDebtSchema(t)));

const { errors, defineField, handleSubmit, isSubmitting } = useForm<DebtFormInput>({
    validationSchema: schema,
    initialValues: {
        debtor_ulid: '',
        amount: '',
        date: '',
        description: '',
    },
});

const [debtorUlidValue] = defineField('debtor_ulid');
const [amountValue] = defineField('amount');
const [dateValue] = defineField('date');
const [descriptionValue] = defineField('description');

onMounted(async () => {
    if (defaultDistrictUlid.value === null) {
        return;
    }

    debtors.value = await fetchDistrictDebtors(defaultDistrictUlid.value);
});

const onSubmit = handleSubmit(async (values) => {
    formError.value = '';

    if (defaultDistrictUlid.value === null) {
        formError.value = t('dashboard.default_district_missing');
        return;
    }

    try {
        await createDebt(defaultDistrictUlid.value, values.debtor_ulid, {
            amount: values.amount,
            date: values.date,
            description: values.description || null,
        });
        await router.push({ name: 'dashboard' });
    } catch (error) {
        formError.value = isApiError(error) && typeof error.response?.data?.message === 'string'
            ? error.response.data.message
            : t('operations.save_error');
    }
});
</script>
