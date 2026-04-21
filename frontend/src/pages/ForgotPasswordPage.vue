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
                <h1 class="text-3xl font-semibold tracking-[-0.04em] text-[var(--lex-text)]">{{ t('auth.forgot.title') }}</h1>
                <p class="text-sm leading-[1.7] text-[var(--lex-muted)]">{{ t('auth.forgot.body') }}</p>
            </div>
        </div>

        <form class="grid gap-4" @submit.prevent="onSubmit">
            <label class="grid gap-2">
                <span class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--lex-muted)]">{{ t('auth.forgot.email_label') }}</span>
                <input v-model="emailValue" type="email" autocomplete="email" class="w-full rounded-sm border border-[var(--lex-border-strong)] bg-white px-4 py-3 text-[var(--lex-text)] outline-none transition focus:border-[var(--lex-accent)] focus:shadow-[0_0_0_3px_rgba(36,65,107,0.08)]" :placeholder="t('auth.forgot.email_placeholder')" />
                <p v-if="errors.email" class="text-sm text-[var(--lex-danger)]">{{ errors.email }}</p>
            </label>

            <button type="submit" :disabled="isSubmitting" class="inline-flex cursor-pointer items-center justify-center rounded-sm bg-[var(--lex-accent)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[var(--lex-accent-hover)] disabled:cursor-not-allowed disabled:opacity-70">
                {{ isSubmitting ? t('auth.shared.working') : t('auth.forgot.submit') }}
            </button>
        </form>

        <div v-if="submitMessage || submitError" class="mt-4 grid gap-3">
            <p v-if="submitMessage" class="rounded-sm bg-[#e8f4ee] px-5 py-4 text-sm leading-6 text-[var(--lex-success)]">{{ submitMessage }}</p>
            <p v-if="submitError" class="rounded-sm bg-[#fbeceb] px-5 py-4 text-sm leading-6 text-[var(--lex-danger)]">{{ submitError }}</p>
        </div>

        <div class="mt-4 grid gap-3 border-t border-[var(--lex-border)] pt-4 sm:grid-cols-2">
            <RouterLink class="inline-flex min-h-12 items-center justify-center rounded-sm border border-[var(--lex-border)] bg-[var(--lex-surface-muted)] px-4 py-3 text-center text-sm font-semibold leading-tight text-[var(--lex-text)] transition hover:border-[var(--lex-border-strong)] hover:bg-[#f2eadc]" :to="{ name: 'login' }">{{ t('auth.links.login') }}</RouterLink>
            <RouterLink class="inline-flex min-h-12 items-center justify-center rounded-sm border border-[var(--lex-border)] bg-[var(--lex-surface-muted)] px-4 py-3 text-center text-sm font-semibold leading-tight text-[var(--lex-text)] transition hover:border-[var(--lex-border-strong)] hover:bg-[#f2eadc]" :to="{ name: 'register' }">{{ t('auth.links.register') }}</RouterLink>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from 'vee-validate';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import { createForgotPasswordSchema, type ForgotPasswordInput, forgotPassword } from '@/api/auth';
import AuthLayout from '@/layouts/AuthLayout.vue';

const { t } = useI18n();
const submitError = ref('');
const submitMessage = ref('');
const { defineField, errors, handleSubmit, isSubmitting, setErrors } = useForm<ForgotPasswordInput>({
    initialValues: {
        email: '',
    },
});

const [emailValue] = defineField('email');
const forgotPasswordSchema = createForgotPasswordSchema(t);

const onSubmit = handleSubmit(async (values) => {
    submitError.value = '';
    submitMessage.value = '';
    setErrors({});

    const result = forgotPasswordSchema.safeParse(values);

    if (!result.success) {
        setErrors({
            email: result.error.flatten().fieldErrors.email?.[0],
        });

        return;
    }

    try {
        await forgotPassword(result.data);
        submitMessage.value = t('auth.forgot.success');
    } catch {
        submitError.value = t('auth.shared.generic_error');
    }
});
</script>
