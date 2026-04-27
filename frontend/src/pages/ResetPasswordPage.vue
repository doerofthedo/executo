<template>
    <AuthLayout>
        <div class="lex-auth-inner">
            <RouterLink :to="{ name: 'login' }" class="lex-brand">
                {{ t('app.name') }}
            </RouterLink>
            <div class="lex-form-heading-group">
                <h1 class="lex-form-title">{{ t('auth.reset.title') }}</h1>
            </div>

            <form v-if="!resetComplete" class="lex-form" @submit.prevent="onSubmit">
                <p class="lex-form-body">
                    {{ token !== '' ? t('auth.reset.token_ready') : t('auth.reset.token_missing') }}
                </p>

                <label class="lex-form-field">
                    <span :class="['lex-input-label', errors.password ? 'lex-input-label-error' : '']">{{ t('auth.form.password_label') }}</span>
                    <input v-model="passwordValue" type="password" autocomplete="new-password" autofocus :class="['lex-input', errors.password ? 'lex-input-error' : '']" />
                    <p v-if="errors.password" class="lex-input-error-message">{{ errors.password }}</p>
                </label>

                <label class="lex-form-field">
                    <span :class="['lex-input-label', errors.password_confirmation ? 'lex-input-label-error' : '']">{{ t('auth.register.password_confirmation_label') }}</span>
                    <input v-model="passwordConfirmationValue" type="password" autocomplete="new-password" :class="['lex-input', errors.password_confirmation ? 'lex-input-error' : '']" />
                    <p v-if="errors.password_confirmation" class="lex-input-error-message">{{ errors.password_confirmation }}</p>
                </label>

                <button type="submit" :disabled="isSubmitting || token === ''" class="lex-button lex-button-primary">
                    {{ isSubmitting ? t('auth.shared.working') : t('auth.reset.submit') }}
                </button>
            </form>

            <div v-else class="lex-form">
                <p class="lex-form-message lex-form-message-success">{{ t('auth.reset.success') }}</p>
                <RouterLink class="lex-button lex-button-primary" :to="{ name: 'login' }">
                    {{ t('auth.reset.login_now') }}
                </RouterLink>
            </div>

            <div v-if="submitError" class="lex-form-error">
                <p class="lex-form-message lex-form-message-error">{{ submitError }}</p>
            </div>

            <div v-if="!resetComplete" class="lex-form-section lex-auth-footer-nav sm:grid-cols-2">
                <RouterLink class="lex-button lex-button-secondary" :to="{ name: 'login' }">{{ t('auth.links.login') }}</RouterLink>
                <RouterLink class="lex-button lex-button-secondary" :to="{ name: 'register' }">{{ t('auth.links.register') }}</RouterLink>
            </div>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm } from 'vee-validate';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';
import { createResetPasswordSchema, type ResetPasswordInput, resetPassword } from '@/api/auth';
import { toTypedSchema } from '@vee-validate/zod';
import AuthLayout from '@/layouts/AuthLayout.vue';

const { t } = useI18n();
const route = useRoute();
const submitError = ref('');
const resetComplete = ref(false);
const token = computed(() => (typeof route.query.token === 'string' ? route.query.token : ''));
const resetPasswordSchema = computed(() => toTypedSchema(createResetPasswordSchema(t)));

const { defineField, errors, handleSubmit, isSubmitting, setFieldError } = useForm<ResetPasswordInput>({
    validationSchema: resetPasswordSchema,
    initialValues: {
        token: token.value,
        password: '',
        password_confirmation: '',
    },
});

const [passwordValue] = defineField('password');
const [passwordConfirmationValue] = defineField('password_confirmation');

const onSubmit = handleSubmit(async (values) => {
    submitError.value = '';
    setFieldError('password', undefined);
    setFieldError('password_confirmation', undefined);

    try {
        await resetPassword({
            ...values,
            token: token.value,
        });
        setFieldError('password', undefined);
        setFieldError('password_confirmation', undefined);
        resetComplete.value = true;
    } catch {
        setFieldError('password', undefined);
        setFieldError('password_confirmation', undefined);
        submitError.value = t('auth.shared.generic_error');
    }
});
</script>
