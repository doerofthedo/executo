<template>
    <AuthLayout>
        <div class="mb-6 grid gap-6">
            <RouterLink
                :to="{ name: 'login' }"
                class="inline-flex items-center text-[2.25rem] leading-[0.95] font-semibold uppercase tracking-[0.12em] text-[var(--lex-muted)] [font-family:'Iowan_Old_Style','Palatino_Linotype','Book_Antiqua',Georgia,serif]"
            >
                {{ t('app.name') }}
            </RouterLink>
            <div class="grid gap-1">
                <h1 class="text-3xl font-semibold tracking-[-0.04em] text-[var(--lex-text)]">
                    {{ verifyMode ? t('auth.register.verify_title') : t('auth.register.title') }}
                </h1>
                <p class="text-sm leading-[1.7] text-[var(--lex-muted)]">
                    {{ verifyMode ? t('auth.register.verify_body') : t('auth.register.body') }}
                </p>
            </div>
        </div>

        <form v-if="!verifyMode && !registrationComplete" class="grid gap-4" @submit.prevent="onRegister">
            <label class="grid gap-2">
                <span class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--lex-muted)]">{{ t('auth.register.name_label') }}</span>
                <input v-model="nameValue" type="text" class="w-full rounded-sm border border-[var(--lex-border-strong)] bg-white px-4 py-3 text-[var(--lex-text)] outline-none transition focus:border-[var(--lex-accent)] focus:shadow-[0_0_0_3px_rgba(36,65,107,0.08)]" :placeholder="t('auth.register.name_placeholder')" />
                <p v-if="errors.name" class="text-sm text-[var(--lex-danger)]">{{ errors.name }}</p>
            </label>

            <label class="grid gap-2">
                <span class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--lex-muted)]">{{ t('auth.register.surname_label') }}</span>
                <input v-model="surnameValue" type="text" class="w-full rounded-sm border border-[var(--lex-border-strong)] bg-white px-4 py-3 text-[var(--lex-text)] outline-none transition focus:border-[var(--lex-accent)] focus:shadow-[0_0_0_3px_rgba(36,65,107,0.08)]" :placeholder="t('auth.register.surname_placeholder')" />
                <p v-if="errors.surname" class="text-sm text-[var(--lex-danger)]">{{ errors.surname }}</p>
            </label>

            <label class="grid gap-2">
                <span class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--lex-muted)]">{{ t('auth.register.email_label') }}</span>
                <input v-model="emailValue" type="email" autocomplete="email" class="w-full rounded-sm border border-[var(--lex-border-strong)] bg-white px-4 py-3 text-[var(--lex-text)] outline-none transition focus:border-[var(--lex-accent)] focus:shadow-[0_0_0_3px_rgba(36,65,107,0.08)]" :placeholder="t('auth.register.email_placeholder')" />
                <p v-if="errors.email" class="text-sm text-[var(--lex-danger)]">{{ errors.email }}</p>
            </label>

            <label class="grid gap-2">
                <span class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--lex-muted)]">{{ t('auth.form.password_label') }}</span>
                <input v-model="passwordValue" type="password" autocomplete="new-password" class="w-full rounded-sm border border-[var(--lex-border-strong)] bg-white px-4 py-3 text-[var(--lex-text)] outline-none transition focus:border-[var(--lex-accent)] focus:shadow-[0_0_0_3px_rgba(36,65,107,0.08)]" :placeholder="t('auth.register.password_placeholder')" />
                <p v-if="errors.password" class="text-sm text-[var(--lex-danger)]">{{ errors.password }}</p>
            </label>

            <label class="grid gap-2">
                <span class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--lex-muted)]">{{ t('auth.register.password_confirmation_label') }}</span>
                <input v-model="passwordConfirmationValue" type="password" autocomplete="new-password" class="w-full rounded-sm border border-[var(--lex-border-strong)] bg-white px-4 py-3 text-[var(--lex-text)] outline-none transition focus:border-[var(--lex-accent)] focus:shadow-[0_0_0_3px_rgba(36,65,107,0.08)]" :placeholder="t('auth.register.password_confirmation_placeholder')" />
                <p v-if="errors.password_confirmation" class="text-sm text-[var(--lex-danger)]">{{ errors.password_confirmation }}</p>
            </label>

            <button type="submit" :disabled="isSubmitting" class="inline-flex cursor-pointer items-center justify-center rounded-sm bg-[var(--lex-accent)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[var(--lex-accent-hover)] disabled:cursor-not-allowed disabled:opacity-70">
                {{ isSubmitting ? t('auth.shared.working') : t('auth.register.submit') }}
            </button>
        </form>

        <div v-else-if="registrationComplete" class="grid gap-4">
            <p class="rounded-sm bg-[#e8f4ee] px-5 py-4 text-sm leading-6 text-[var(--lex-success)]">{{ infoMessage }}</p>
            <button type="button" class="inline-flex min-h-12 items-center justify-center rounded-sm border border-[var(--lex-border)] bg-[var(--lex-surface-muted)] px-4 py-3 text-center text-sm font-semibold leading-tight text-[var(--lex-text)] transition hover:border-[var(--lex-border-strong)] hover:bg-[#f2eadc] disabled:cursor-not-allowed disabled:opacity-70" :disabled="resending" @click="onResendVerification">
                {{ resending ? t('auth.shared.working') : t('auth.register.resend_verification') }}
            </button>
        </div>

        <form v-else class="grid gap-4" @submit.prevent="onVerify">
            <p class="text-sm leading-[1.7] text-[var(--lex-muted)]">
                {{ signedVerificationUrl ? t('auth.register.verification_link_ready') : t('auth.register.verification_link_missing') }}
            </p>
            <button type="submit" :disabled="verifying || signedVerificationUrl === ''" class="inline-flex cursor-pointer items-center justify-center rounded-sm bg-[var(--lex-accent)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[var(--lex-accent-hover)] disabled:cursor-not-allowed disabled:opacity-70">
                {{ verifying ? t('auth.shared.working') : t('auth.register.verify_submit') }}
            </button>
        </form>

        <div v-if="messages.length > 0 || errorMessage || infoMessage" class="mt-4 grid gap-3">
            <ul v-if="messages.length > 0" class="rounded-sm bg-[#fbeceb] px-5 py-4 text-sm leading-6 text-[var(--lex-danger)]">
                <li v-for="message in messages" :key="message">{{ message }}</li>
            </ul>
            <p v-if="errorMessage" class="rounded-sm bg-[#fbeceb] px-5 py-4 text-sm leading-6 text-[var(--lex-danger)]">{{ errorMessage }}</p>
            <p v-if="infoMessage && !registrationComplete" class="rounded-sm bg-[#e8f4ee] px-5 py-4 text-sm leading-6 text-[var(--lex-success)]">{{ infoMessage }}</p>
        </div>

        <div class="mt-4 grid gap-3 border-t border-[var(--lex-border)] pt-4 sm:grid-cols-2">
            <RouterLink class="inline-flex min-h-12 items-center justify-center rounded-sm border border-[var(--lex-border)] bg-[var(--lex-surface-muted)] px-4 py-3 text-center text-sm font-semibold leading-tight text-[var(--lex-text)] transition hover:border-[var(--lex-border-strong)] hover:bg-[#f2eadc]" :to="{ name: 'login' }">{{ t('auth.links.login') }}</RouterLink>
            <RouterLink class="inline-flex min-h-12 items-center justify-center rounded-sm border border-[var(--lex-border)] bg-[var(--lex-surface-muted)] px-4 py-3 text-center text-sm font-semibold leading-tight text-[var(--lex-text)] transition hover:border-[var(--lex-border-strong)] hover:bg-[#f2eadc]" :to="{ name: 'forgot-password' }">{{ t('auth.links.forgot') }}</RouterLink>
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
