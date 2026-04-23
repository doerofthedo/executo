<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-panel lex-panel-header p-8">
                <p class="lex-page-eyebrow">{{ t('operations.customer_create_eyebrow') }}</p>
                <h1 class="lex-page-title">{{ t('operations.customer_create_title') }}</h1>
            </section>

            <section class="lex-panel p-8">
                <form class="lex-form" @submit.prevent="onSubmit">
                    <div class="lex-settings-grid">
                        <label class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.case_number') }}</span>
                            <input v-model="caseNumberValue" class="lex-input" />
                        </label>

                        <label class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.customer_type') }}</span>
                            <select v-model="typeValue" class="lex-input">
                                <option value="physical">{{ t('operations.type_physical') }}</option>
                                <option value="legal">{{ t('operations.type_legal') }}</option>
                            </select>
                        </label>

                        <label v-if="typeValue === 'physical'" class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.first_name') }}</span>
                            <input v-model="firstNameValue" class="lex-input" />
                            <p v-if="errors.first_name" class="lex-input-error-message">{{ errors.first_name }}</p>
                        </label>

                        <label v-if="typeValue === 'physical'" class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.last_name') }}</span>
                            <input v-model="lastNameValue" class="lex-input" />
                            <p v-if="errors.last_name" class="lex-input-error-message">{{ errors.last_name }}</p>
                        </label>

                        <label v-if="typeValue === 'legal'" class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.company_name') }}</span>
                            <input v-model="companyNameValue" class="lex-input" />
                            <p v-if="errors.company_name" class="lex-input-error-message">{{ errors.company_name }}</p>
                        </label>

                        <label class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.email') }}</span>
                            <input v-model="emailValue" type="email" class="lex-input" />
                            <p v-if="errors.email" class="lex-input-error-message">{{ errors.email }}</p>
                        </label>

                        <label class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.phone') }}</span>
                            <input v-model="phoneValue" class="lex-input" />
                        </label>

                        <label v-if="typeValue === 'physical'" class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.personal_code') }}</span>
                            <input v-model="personalCodeValue" class="lex-input" />
                        </label>

                        <label v-if="typeValue === 'physical'" class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.date_of_birth') }}</span>
                            <input v-model="dateOfBirthValue" type="date" class="lex-input" />
                        </label>

                        <label v-if="typeValue === 'legal'" class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.registration_number') }}</span>
                            <input v-model="registrationNumberValue" class="lex-input" />
                        </label>

                        <label v-if="typeValue === 'legal'" class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.contact_person') }}</span>
                            <input v-model="contactPersonValue" class="lex-input" />
                        </label>
                    </div>

                    <div class="lex-section-actions">
                        <button type="submit" class="lex-button lex-button-primary" :disabled="isSubmitting">
                            {{ isSubmitting ? t('operations.saving') : t('operations.create_customer') }}
                        </button>
                        <p v-if="formError !== ''" class="lex-form-message lex-form-message-error">{{ formError }}</p>
                    </div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import axios from 'axios';
import { useForm } from 'vee-validate';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { createCustomer, createCustomerSchema, type CustomerFormInput } from '@/api/operations';
import { toTypedSchema } from '@vee-validate/zod';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();
const formError = ref('');

const defaultDistrictUlid = computed(() => typeof route.params.district === 'string'
    ? route.params.district
    : (authStore.user?.default_district_ulid ?? null));
const schema = computed(() => toTypedSchema(createCustomerSchema(t)));

const { errors, defineField, handleSubmit, isSubmitting } = useForm<CustomerFormInput>({
    validationSchema: schema,
    initialValues: {
        case_number: '',
        type: 'physical',
        email: '',
        phone: '',
        first_name: '',
        last_name: '',
        personal_code: '',
        date_of_birth: '',
        company_name: '',
        registration_number: '',
        contact_person: '',
    },
});

const [caseNumberValue] = defineField('case_number');
const [typeValue] = defineField('type');
const [emailValue] = defineField('email');
const [phoneValue] = defineField('phone');
const [firstNameValue] = defineField('first_name');
const [lastNameValue] = defineField('last_name');
const [personalCodeValue] = defineField('personal_code');
const [dateOfBirthValue] = defineField('date_of_birth');
const [companyNameValue] = defineField('company_name');
const [registrationNumberValue] = defineField('registration_number');
const [contactPersonValue] = defineField('contact_person');

const onSubmit = handleSubmit(async (values) => {
    formError.value = '';

    if (defaultDistrictUlid.value === null) {
        formError.value = t('dashboard.default_district_missing');
        return;
    }

    try {
        await createCustomer(defaultDistrictUlid.value, {
            ...values,
            case_number: values.case_number || null,
            email: values.email || null,
            phone: values.phone || null,
            first_name: values.first_name || null,
            last_name: values.last_name || null,
            personal_code: values.personal_code || null,
            date_of_birth: values.date_of_birth || null,
            company_name: values.company_name || null,
            registration_number: values.registration_number || null,
            contact_person: values.contact_person || null,
        });

        await router.push({ name: 'dashboard' });
    } catch (error) {
        formError.value = axios.isAxiosError(error) && typeof error.response?.data?.message === 'string'
            ? error.response.data.message
            : t('operations.save_error');
    }
});
</script>
