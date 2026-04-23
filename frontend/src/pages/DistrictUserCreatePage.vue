<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-panel lex-panel-header p-8">
                <p class="lex-page-eyebrow">{{ t('operations.user_create_eyebrow') }}</p>
                <h1 class="lex-page-title">{{ t('operations.user_create_title') }}</h1>
            </section>

            <section class="lex-panel p-8">
                <form class="lex-form" @submit.prevent="onSubmit">
                    <div class="lex-settings-grid">
                        <label class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.email') }}</span>
                            <input v-model="emailValue" type="email" class="lex-input" />
                            <p v-if="errors.email" class="lex-input-error-message">{{ errors.email }}</p>
                        </label>

                        <label class="lex-form-field">
                            <span class="lex-input-label">{{ t('operations.role') }}</span>
                            <select v-model="roleValue" class="lex-input">
                                <option value="district.admin">{{ t('operations.role_district_admin') }}</option>
                                <option value="district.manager">{{ t('operations.role_district_manager') }}</option>
                                <option value="district.user">{{ t('operations.role_district_user') }}</option>
                            </select>
                        </label>
                    </div>

                    <div class="lex-section-actions">
                        <button type="submit" class="lex-button lex-button-primary" :disabled="isSubmitting">
                            {{ isSubmitting ? t('operations.saving') : t('operations.create_user') }}
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
import { createDistrictUserSchema, inviteDistrictUser, type DistrictUserFormInput } from '@/api/users';
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
const schema = computed(() => toTypedSchema(createDistrictUserSchema(t)));

const { errors, defineField, handleSubmit, isSubmitting } = useForm<DistrictUserFormInput>({
    validationSchema: schema,
    initialValues: {
        email: '',
        role: 'district.user',
    },
});

const [emailValue] = defineField('email');
const [roleValue] = defineField('role');

const onSubmit = handleSubmit(async (values) => {
    formError.value = '';

    if (defaultDistrictUlid.value === null) {
        formError.value = t('dashboard.default_district_missing');
        return;
    }

    try {
        await inviteDistrictUser(defaultDistrictUlid.value, values.email, values.role);
        await router.push({ name: 'dashboard' });
    } catch (error) {
        formError.value = axios.isAxiosError(error) && typeof error.response?.data?.message === 'string'
            ? error.response.data.message
            : t('operations.save_error');
    }
});
</script>
