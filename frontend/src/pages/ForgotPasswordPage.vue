<template>
    <AuthLayout>
        <div class="lex-auth-inner">
            <RouterLink :to="{ name: 'login' }" class="lex-brand">
                {{ t('app.name') }}
            </RouterLink>
            <div class="lex-form-heading-group">
                <h1 class="lex-form-title">{{ t('auth.forgot.title') }}</h1>
            </div>

            <form v-if="submitMessage === ''" class="lex-form" @submit.prevent="onSubmit">
                <label class="lex-form-field">
                    <span :class="['lex-input-label', errors.email ? 'lex-input-label-error' : '']">{{ t('auth.forgot.email_label') }}</span>
                    <input v-model="emailValue" type="email" autocomplete="email" :class="['lex-input', errors.email ? 'lex-input-error' : '']" />
                    <p v-if="errors.email" class="lex-input-error-message">{{ errors.email }}</p>
                </label>

                <button type="submit" :disabled="isSubmitting" class="lex-button lex-button-primary">
                    {{ isSubmitting ? t('auth.shared.working') : t('auth.forgot.submit') }}
                </button>
            </form>

            <div v-if="submitMessage || submitError" class="lex-auth-status">
                <p v-if="submitMessage" class="lex-form-message lex-form-message-success">{{ submitMessage }}</p>
                <p v-if="submitError" class="lex-form-message lex-form-message-error">{{ submitError }}</p>
            </div>

            <div class="lex-form-section lex-auth-footer-nav sm:grid-cols-2">
                <RouterLink class="lex-button lex-button-secondary" :to="{ name: 'login' }">{{ t('auth.links.login') }}</RouterLink>
                <RouterLink class="lex-button lex-button-secondary" :to="{ name: 'register' }">{{ t('auth.links.register') }}</RouterLink>
            </div>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from 'vee-validate';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import { createForgotPasswordSchema, type ForgotPasswordInput, forgotPassword } from '@/api/auth';
import { toTypedSchema } from '@vee-validate/zod';
import AuthLayout from '@/layouts/AuthLayout.vue';

const { t } = useI18n();
const submitError = ref('');
const submitMessage = ref('');
const forgotPasswordSchema = createForgotPasswordSchema(t);
const { defineField, errors, handleSubmit, isSubmitting, setFieldError } = useForm<ForgotPasswordInput>({
    validationSchema: toTypedSchema(forgotPasswordSchema),
    initialValues: {
        email: '',
    },
});

const [emailValue] = defineField('email');

const onSubmit = handleSubmit(async (values) => {
    submitError.value = '';
    submitMessage.value = '';
    setFieldError('email', undefined);

    try {
        await forgotPassword(values);
        setFieldError('email', undefined);
        submitMessage.value = t('auth.forgot.success');
    } catch {
        setFieldError('email', undefined);
        submitError.value = t('auth.shared.generic_error');
    }
});
</script>
