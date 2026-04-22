<template>
    <AuthLayout>
        <div class="lex-auth-inner">
            <div class="lex-form-header">
                <RouterLink
                    :to="{ name: 'login' }"
                    class="lex-brand"
                >
                    {{ t('app.name') }}
                </RouterLink>
                <div class="lex-form-heading-group">
                    <h1 class="lex-form-title">
                        {{ verifyMode ? t('auth.register.verify_title') : t('auth.register.title') }}
                    </h1>
                </div>
            </div>

            <form v-if="!verifyMode && !registrationComplete" class="lex-form" @submit.prevent="onRegister">
                <label class="lex-form-field">
                    <span :class="['lex-input-label', errors.name ? 'lex-input-label-error' : '']">{{ t('auth.register.name_label') }}</span>
                    <input v-model="nameValue" type="text" autofocus :class="['lex-input', errors.name ? 'lex-input-error' : '']" />
                    <p v-if="errors.name" class="lex-input-error-message">{{ errors.name }}</p>
                </label>

                <label class="lex-form-field">
                    <span :class="['lex-input-label', errors.surname ? 'lex-input-label-error' : '']">{{ t('auth.register.surname_label') }}</span>
                    <input v-model="surnameValue" type="text" :class="['lex-input', errors.surname ? 'lex-input-error' : '']" />
                    <p v-if="errors.surname" class="lex-input-error-message">{{ errors.surname }}</p>
                </label>

                <label class="lex-form-field">
                    <span :class="['lex-input-label', errors.email ? 'lex-input-label-error' : '']">{{ t('auth.register.email_label') }}</span>
                    <input v-model="emailValue" type="email" autocomplete="email" :class="['lex-input', errors.email ? 'lex-input-error' : '']" />
                    <p v-if="errors.email" class="lex-input-error-message">{{ errors.email }}</p>
                </label>

                <label class="lex-form-field">
                    <span :class="['lex-input-label', errors.password ? 'lex-input-label-error' : '']">{{ t('auth.form.password_label') }}</span>
                    <input v-model="passwordValue" type="password" autocomplete="new-password" :class="['lex-input', errors.password ? 'lex-input-error' : '']" />
                    <p v-if="errors.password" class="lex-input-error-message">{{ errors.password }}</p>
                </label>

                <label class="lex-form-field">
                    <span :class="['lex-input-label', errors.password_confirmation ? 'lex-input-label-error' : '']">{{ t('auth.register.password_confirmation_label') }}</span>
                    <input v-model="passwordConfirmationValue" type="password" autocomplete="new-password" :class="['lex-input', errors.password_confirmation ? 'lex-input-error' : '']" />
                    <p v-if="errors.password_confirmation" class="lex-input-error-message">{{ errors.password_confirmation }}</p>
                </label>

                <button type="submit" :disabled="isSubmitting" class="lex-button lex-button-primary">
                    {{ isSubmitting ? t('auth.shared.working') : t('auth.register.submit') }}
                </button>
            </form>

            <div v-else-if="registrationComplete" class="lex-form">
                <p class="lex-form-message lex-form-message-success">{{ infoMessage }}</p>
                <button type="button" class="lex-button lex-button-secondary" :disabled="resending" @click="onResendVerification">
                    {{ resending ? t('auth.shared.working') : t('auth.register.resend_verification') }}
                </button>
            </div>

            <form v-else class="lex-form" @submit.prevent="onVerify">
                <p class="lex-form-body">
                    {{ signedVerificationUrl ? t('auth.register.verification_link_ready') : t('auth.register.verification_link_missing') }}
                </p>
                <button type="submit" :disabled="verifying || signedVerificationUrl === ''" class="lex-button lex-button-primary">
                    {{ verifying ? t('auth.shared.working') : t('auth.register.verify_submit') }}
                </button>
            </form>

            <div v-if="messages.length > 0 || errorMessage || infoMessage" class="lex-auth-status">
                <ul v-if="messages.length > 0" class="lex-form-message lex-form-message-error">
                    <li v-for="message in messages" :key="message">{{ message }}</li>
                </ul>
                <p v-if="errorMessage" class="lex-form-message lex-form-message-error">{{ errorMessage }}</p>
                <p v-if="infoMessage && !registrationComplete" class="lex-form-message lex-form-message-success">{{ infoMessage }}</p>
            </div>

            <div class="lex-form-section lex-auth-footer-nav sm:grid-cols-2">
                <RouterLink class="lex-button lex-button-secondary" :to="{ name: 'login' }">{{ t('auth.links.login') }}</RouterLink>
                <RouterLink class="lex-button lex-button-secondary" :to="{ name: 'forgot-password' }">{{ t('auth.links.forgot') }}</RouterLink>
            </div>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import axios from 'axios';
import { computed, ref } from 'vue';
import { useForm } from 'vee-validate';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';
import { createRegisterSchema, register, requestEmailVerification } from '@/api/auth';
import AuthLayout from '@/layouts/AuthLayout.vue';

const { t, locale } = useI18n();
const route = useRoute();
const errorMessage = ref('');
const infoMessage = ref('');
const messages = ref<string[]>([]);
const submittedEmail = ref('');
const resending = ref(false);
const verifying = ref(false);

const verifyMode = computed(() => route.query.verify === '1');
const signedVerificationUrl = computed(() => (typeof route.query.url === 'string' ? route.query.url : ''));
const registrationComplete = computed(() => !verifyMode.value && infoMessage.value !== '');

const { defineField, errors, handleSubmit, isSubmitting, setErrors } = useForm({
    initialValues: {
        name: '',
        surname: '',
        email: '',
        password: '',
        password_confirmation: '',
        locale: locale.value as 'lv' | 'en',
    },
});

const [nameValue] = defineField('name');
const [surnameValue] = defineField('surname');
const [emailValue] = defineField('email');
const [passwordValue] = defineField('password');
const [passwordConfirmationValue] = defineField('password_confirmation');
const registerSchema = createRegisterSchema(t);

const onRegister = handleSubmit(async (values) => {
    errorMessage.value = '';
    infoMessage.value = '';
    messages.value = [];
    setErrors({});

    const result = registerSchema.safeParse({
        ...values,
        locale: locale.value as 'lv' | 'en',
    });

    if (!result.success) {
        const fieldErrors = result.error.flatten().fieldErrors;

        setErrors({
            name: fieldErrors.name?.[0],
            surname: fieldErrors.surname?.[0],
            email: fieldErrors.email?.[0],
            password: fieldErrors.password?.[0],
            password_confirmation: fieldErrors.password_confirmation?.[0],
        });

        return;
    }

    try {
        await register(result.data);
        submittedEmail.value = result.data.email;
        infoMessage.value = t('auth.register.success');
    } catch (error: unknown) {
        if (axios.isAxiosError(error) && error.response?.status === 422 && typeof error.response.data === 'object' && error.response.data !== null && 'errors' in error.response.data) {
            const responseErrors = error.response.data.errors as Record<string, string[]>;
            messages.value = Object.values(responseErrors).flat();
            return;
        }

        errorMessage.value = t('auth.shared.generic_error');
    }
});

async function onResendVerification(): Promise<void> {
    if (submittedEmail.value === '') {
        return;
    }

    resending.value = true;
    errorMessage.value = '';

    try {
        await requestEmailVerification(submittedEmail.value);
        infoMessage.value = t('auth.register.verification_resent');
    } catch {
        errorMessage.value = t('auth.shared.generic_error');
    } finally {
        resending.value = false;
    }
}

async function onVerify(): Promise<void> {
    if (signedVerificationUrl.value === '') {
        errorMessage.value = t('auth.register.verification_link_missing');
        return;
    }

    verifying.value = true;
    errorMessage.value = '';
    infoMessage.value = '';

    try {
        await axios.get(signedVerificationUrl.value, {
            headers: {
                Accept: 'application/json',
            },
        });
        infoMessage.value = t('auth.register.verified');
    } catch {
        errorMessage.value = t('auth.register.verification_failed');
    } finally {
        verifying.value = false;
    }
}
</script>
