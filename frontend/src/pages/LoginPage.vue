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
                    <h1 class="lex-form-title">{{ t('auth.login.title') }}</h1>
                </div>
            </div>

            <form class="lex-form" @submit.prevent="onSubmit">
                <label class="lex-form-field">
                    <span :class="['lex-input-label', errors.login ? 'lex-input-label-error' : '']">{{ t('auth.form.login_label') }}</span>
                    <input
                        v-model="loginValue"
                        type="text"
                        autocomplete="username"
                        autofocus
                        :class="['lex-input', errors.login ? 'lex-input-error' : '']"
                    />
                    <p v-if="errors.login" class="lex-input-error-message">{{ errors.login }}</p>
                </label>

                <label class="lex-form-field">
                    <span :class="['lex-input-label', errors.password ? 'lex-input-label-error' : '']">{{ t('auth.form.password_label') }}</span>
                    <input
                        v-model="passwordValue"
                        ref="passwordInput"
                        type="password"
                        autocomplete="current-password"
                        :class="['lex-input', errors.password ? 'lex-input-error' : '']"
                    />
                    <p v-if="errors.password" class="lex-input-error-message">{{ errors.password }}</p>
                </label>

                <button
                    type="submit"
                    :disabled="isSubmitting"
                    class="lex-button lex-button-primary"
                >
                    {{ isSubmitting ? t('auth.form.submitting') : t('auth.form.submit') }}
                </button>
            </form>

            <div v-if="submitError !== null" class="lex-form-error">
                <p class="lex-input-error-message">{{ submitError }}</p>
            </div>

            <div class="lex-form-section lex-auth-footer-nav sm:grid-cols-2">
                <RouterLink class="lex-button lex-button-secondary" :to="{ name: 'register' }">{{ t('auth.links.register') }}</RouterLink>
                <RouterLink class="lex-button lex-button-secondary" :to="{ name: 'forgot-password' }">{{ t('auth.links.forgot') }}</RouterLink>
            </div>

            <div class="lex-form-section lex-auth-locale">
                <span class="lex-input-label">{{ t('auth.locale.label') }}</span>
                <button
                    type="button"
                    :aria-pressed="locale === 'lv'"
                    :class="localeButtonClass('lv')"
                    @click="setLocale('lv')"
                >
                    {{ t('auth.locale.lv') }}
                </button>
                <button
                    type="button"
                    :aria-pressed="locale === 'en'"
                    :class="localeButtonClass('en')"
                    @click="setLocale('en')"
                >
                    {{ t('auth.locale.en') }}
                </button>
            </div>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import { useForm } from 'vee-validate';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import type { LoginInput } from '@/api/auth';
import { createLoginSchema } from '@/api/auth';
import { toTypedSchema } from '@vee-validate/zod';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { useAuthStore } from '@/stores/auth';
import { setPreferredLocale } from '@/i18n';

const { t, locale } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const submitError = ref<string | null>(null);
const passwordInput = ref<HTMLInputElement | null>(null);
const loginSchema = createLoginSchema(t);
const { errors, defineField, handleSubmit, isSubmitting, setFieldError } = useForm<LoginInput>({
    validationSchema: toTypedSchema(loginSchema),
    initialValues: {
        login: '',
        password: '',
    },
});

const [loginValue] = defineField('login');
const [passwordValue] = defineField('password');

function localeButtonClass(value: 'lv' | 'en'): string[] {
    return [
        'lex-button',
        'lex-button-inline',
        ...(locale.value === value ? ['lex-button-inline-active'] : []),
    ];
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
    setFieldError('login', undefined);
    setFieldError('password', undefined);

    try {
        await authStore.signIn(values);
        window.location.assign(redirectTarget.value);
    } catch {
        const message = t('auth.form.invalid_credentials');

        submitError.value = message;
        setFieldError('login', undefined);
        setFieldError('password', undefined);
        await nextTick();
        passwordInput.value?.focus();
        passwordInput.value?.select();
    }
});
</script>
