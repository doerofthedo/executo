<template>
    <AuthLayout panel-class="max-w-[28rem]">
        <div class="mx-auto w-full max-w-[24rem]">
            <div class="mb-5 grid gap-4">
                <RouterLink
                    :to="{ name: 'login' }"
                    class="inline-flex items-center text-[2.25rem] leading-[0.95] font-semibold uppercase tracking-[0.12em] text-[var(--lex-muted)] [font-family:'Iowan_Old_Style','Palatino_Linotype','Book_Antiqua',Georgia,serif]"
                >
                    {{ t('app.name') }}
                </RouterLink>
                <div class="grid gap-0.5">
                    <h1 class="text-3xl font-semibold tracking-[-0.04em] text-[var(--lex-text)]">{{ t('auth.login.title') }}</h1>
                </div>
            </div>

            <form class="grid gap-4" @submit.prevent="onSubmit">
                <label class="grid gap-1">
                    <span :class="fieldLabelClass">{{ t('auth.form.login_label') }}</span>
                    <input
                        v-model="loginValue"
                        type="text"
                        autocomplete="username"
                        autofocus
                        :class="inputClass"
                    />
                    <p v-if="errors.login" class="text-sm text-[var(--lex-danger)]">{{ errors.login }}</p>
                </label>

                <label class="grid gap-1">
                    <span :class="fieldLabelClass">{{ t('auth.form.password_label') }}</span>
                    <input
                        v-model="passwordValue"
                        type="password"
                        autocomplete="current-password"
                        :class="inputClass"
                    />
                    <p v-if="errors.password" class="text-sm text-[var(--lex-danger)]">{{ errors.password }}</p>
                </label>

                <button
                    type="submit"
                    :disabled="isSubmitting"
                    :class="primaryButtonClass"
                >
                    {{ isSubmitting ? t('auth.form.submitting') : t('auth.form.submit') }}
                </button>
            </form>

            <div v-if="submitError !== null" class="mt-4 grid gap-3">
                <p class="rounded-sm bg-[#fbeceb] px-5 py-4 text-sm leading-6 text-[var(--lex-danger)]">{{ submitError }}</p>
            </div>

            <div class="mt-5 grid gap-3 border-t border-[var(--lex-border)] pt-5 sm:grid-cols-2">
                <button type="button" :class="secondaryButtonClass" @click="router.push({ name: 'register' })">{{ t('auth.links.register') }}</button>
                <button type="button" :class="secondaryButtonClass" @click="router.push({ name: 'forgot-password' })">{{ t('auth.links.forgot') }}</button>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-center gap-2.5 border-t border-[var(--lex-border)] pt-5">
                <span :class="fieldLabelClass">{{ t('auth.locale.label') }}</span>
                <button
                    type="button"
                    :aria-pressed="locale === 'lv'"
                    :class="inlineButtonClass('lv')"
                    @click="setLocale('lv')"
                >
                    {{ t('auth.locale.lv') }}
                </button>
                <button
                    type="button"
                    :aria-pressed="locale === 'en'"
                    :class="inlineButtonClass('en')"
                    @click="setLocale('en')"
                >
                    {{ t('auth.locale.en') }}
                </button>
            </div>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm } from 'vee-validate';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import type { LoginInput } from '@/api/auth';
import { createLoginSchema } from '@/api/auth';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { useAuthStore } from '@/stores/auth';
import { setPreferredLocale } from '@/i18n';

const { t, locale } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const submitError = ref<string | null>(null);
const { errors, defineField, handleSubmit, isSubmitting, setErrors } = useForm<LoginInput>({
    initialValues: {
        login: '',
        password: '',
    },
});

const [loginValue] = defineField('login');
const [passwordValue] = defineField('password');
const loginSchema = createLoginSchema(t);

const fieldLabelClass = 'text-sm leading-4 text-[var(--lex-muted)]';
const inputClass = 'w-full rounded-sm border border-[var(--lex-border-strong)] bg-white px-4 py-3 text-[var(--lex-text)] transition focus:border-[var(--lex-accent)] focus:shadow-[0_0_0_3px_rgba(36,65,107,0.08)]';
const primaryButtonClass = 'inline-flex min-h-12 cursor-pointer items-center justify-center rounded-sm bg-[var(--lex-accent)] px-4 py-3 text-sm font-semibold leading-5 text-white shadow-[0_10px_18px_rgba(36,65,107,0.12)] transition hover:-translate-y-px hover:bg-[var(--lex-accent-hover)] hover:shadow-[0_14px_24px_rgba(27,51,85,0.18)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--lex-accent)] focus-visible:ring-offset-2 focus-visible:ring-offset-white active:translate-y-px disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0 disabled:hover:shadow-[0_10px_18px_rgba(36,65,107,0.12)]';
const secondaryButtonClass = 'inline-flex min-h-12 cursor-pointer items-center justify-center rounded-sm border border-[var(--lex-secondary-border)] bg-[var(--lex-secondary-surface)] px-4 py-3 text-sm font-semibold leading-5 text-[var(--lex-secondary-text)] shadow-[0_8px_16px_rgba(31,42,55,0.06)] transition hover:-translate-y-px hover:bg-[var(--lex-secondary-surface-hover)] hover:shadow-[0_12px_22px_rgba(31,42,55,0.1)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--lex-accent)] focus-visible:ring-offset-2 focus-visible:ring-offset-white active:translate-y-px';
function inlineButtonClass(value: 'lv' | 'en'): string {
    const base = 'inline-flex min-h-8 cursor-pointer items-center justify-center rounded-sm border px-3 py-1.5 text-sm font-medium leading-4 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--lex-accent)] focus-visible:ring-offset-2 focus-visible:ring-offset-white active:translate-y-px';

    if (locale.value === value) {
        return `${base} border-[var(--lex-inline-active-border)] bg-[var(--lex-inline-active-surface)] text-[var(--lex-inline-active-text)]`;
    }

    return `${base} border-[var(--lex-inline-border)] bg-[var(--lex-inline-surface)] text-[var(--lex-inline-text)] hover:bg-[var(--lex-inline-surface-hover)] hover:text-[var(--lex-text)]`;
}

const redirectTarget = computed(() => {
    const redirect = route.query.redirect;

    return typeof redirect === 'string' && redirect !== '' ? redirect : '/dashboard';
});

function setLocale(value: 'lv' | 'en'): void {
    setPreferredLocale(value);
}

const onSubmit = handleSubmit(async (values) => {
    submitError.value = null;
    setErrors({});

    const result = loginSchema.safeParse(values);

    if (!result.success) {
        const fieldErrors = result.error.flatten().fieldErrors;

        setErrors({
            login: fieldErrors.login?.[0],
            password: fieldErrors.password?.[0],
        });

        return;
    }

    try {
        await authStore.signIn(result.data);
        await router.push(redirectTarget.value);
    } catch {
        const message = t('auth.form.invalid_credentials');

        submitError.value = message;
        setErrors({});
    }
});
</script>
