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
                <h1 class="text-3xl font-semibold tracking-[-0.04em] text-[var(--lex-text)]">{{ t('auth.reset.title') }}</h1>
                <p class="text-sm leading-[1.7] text-[var(--lex-muted)]">{{ t('auth.reset.body') }}</p>
            </div>
        </div>

        <form v-if="!resetComplete" class="grid gap-4" @submit.prevent="onSubmit">
            <p class="text-sm leading-[1.7] text-[var(--lex-muted)]">
                {{ token !== '' ? t('auth.reset.token_ready') : t('auth.reset.token_missing') }}
            </p>

            <label class="grid gap-2">
                <span class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--lex-muted)]">{{ t('auth.reset.email_label') }}</span>
                <input v-model="emailValue" type="email" autocomplete="email" class="w-full rounded-sm border border-[var(--lex-border-strong)] bg-white px-4 py-3 text-[var(--lex-text)] outline-none transition focus:border-[var(--lex-accent)] focus:shadow-[0_0_0_3px_rgba(36,65,107,0.08)]" />
                <p v-if="errors.email" class="text-sm text-[var(--lex-danger)]">{{ errors.email }}</p>
            </label>

            <label class="grid gap-2">
                <span class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--lex-muted)]">{{ t('auth.form.password_label') }}</span>
                <input v-model="passwordValue" type="password" autocomplete="new-password" class="w-full rounded-sm border border-[var(--lex-border-strong)] bg-white px-4 py-3 text-[var(--lex-text)] outline-none transition focus:border-[var(--lex-accent)] focus:shadow-[0_0_0_3px_rgba(36,65,107,0.08)]" />
                <p v-if="errors.password" class="text-sm text-[var(--lex-danger)]">{{ errors.password }}</p>
            </label>

            <label class="grid gap-2">
                <span class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--lex-muted)]">{{ t('auth.register.password_confirmation_label') }}</span>
                <input v-model="passwordConfirmationValue" type="password" autocomplete="new-password" class="w-full rounded-sm border border-[var(--lex-border-strong)] bg-white px-4 py-3 text-[var(--lex-text)] outline-none transition focus:border-[var(--lex-accent)] focus:shadow-[0_0_0_3px_rgba(36,65,107,0.08)]" />
                <p v-if="errors.password_confirmation" class="text-sm text-[var(--lex-danger)]">{{ errors.password_confirmation }}</p>
            </label>

            <button type="submit" :disabled="isSubmitting || token === ''" class="inline-flex cursor-pointer items-center justify-center rounded-sm bg-[var(--lex-accent)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[var(--lex-accent-hover)] disabled:cursor-not-allowed disabled:opacity-70">
                {{ isSubmitting ? t('auth.shared.working') : t('auth.reset.submit') }}
            </button>
        </form>

        <div v-else class="grid gap-4">
            <p class="rounded-sm bg-[#e8f4ee] px-5 py-4 text-sm leading-6 text-[var(--lex-success)]">{{ t('auth.reset.success') }}</p>
            <RouterLink class="inline-flex cursor-pointer items-center justify-center rounded-sm bg-[var(--lex-accent)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[var(--lex-accent-hover)]" :to="{ name: 'login' }">{{ t('auth.reset.login_now') }}</RouterLink>
        </div>

        <div v-if="submitError" class="mt-4 grid gap-3">
            <p class="rounded-sm bg-[#fbeceb] px-5 py-4 text-sm leading-6 text-[var(--lex-danger)]">{{ submitError }}</p>
        </div>

        <div v-if="!resetComplete" class="mt-4 grid gap-3 border-t border-[var(--lex-border)] pt-4 sm:grid-cols-2">
            <RouterLink class="inline-flex min-h-12 items-center justify-center rounded-sm border border-[var(--lex-border)] bg-[var(--lex-surface-muted)] px-4 py-3 text-center text-sm font-semibold leading-tight text-[var(--lex-text)] transition hover:border-[var(--lex-border-strong)] hover:bg-[#f2eadc]" :to="{ name: 'login' }">{{ t('auth.links.login') }}</RouterLink>
            <RouterLink class="inline-flex min-h-12 items-center justify-center rounded-sm border border-[var(--lex-border)] bg-[var(--lex-surface-muted)] px-4 py-3 text-center text-sm font-semibold leading-tight text-[var(--lex-text)] transition hover:border-[var(--lex-border-strong)] hover:bg-[#f2eadc]" :to="{ name: 'register' }">{{ t('auth.links.register') }}</RouterLink>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm } from 'vee-validate';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';
import { type ResetPasswordInput, resetPassword, resetPasswordSchema } from '@/api/auth';
import AuthLayout from '@/layouts/AuthLayout.vue';

const { t } = useI18n();
const route = useRoute();
const submitError = ref('');
const resetComplete = ref(false);
const token = computed(() => (typeof route.query.token === 'string' ? route.query.token : ''));
const emailFromQuery = computed(() => (typeof route.query.email === 'string' ? route.query.email : ''));

const { defineField, errors, handleSubmit, isSubmitting, setErrors } = useForm<ResetPasswordInput>({
    initialValues: {
        email: emailFromQuery.value,
        token: token.value,
        password: '',
        password_confirmation: '',
    },
});

const [emailValue] = defineField('email');
const [passwordValue] = defineField('password');
const [passwordConfirmationValue] = defineField('password_confirmation');

const onSubmit = handleSubmit(async (values) => {
    submitError.value = '';
    setErrors({});

    const result = resetPasswordSchema.safeParse({
        ...values,
        token: token.value,
    });

    if (!result.success) {
        const fieldErrors = result.error.flatten().fieldErrors;

        setErrors({
            email: fieldErrors.email?.[0],
            password: fieldErrors.password?.[0],
            password_confirmation: fieldErrors.password_confirmation?.[0],
        });

        return;
    }

    try {
        await resetPassword(result.data);
        resetComplete.value = true;
    } catch {
        submitError.value = t('auth.shared.generic_error');
    }
});
</script>
