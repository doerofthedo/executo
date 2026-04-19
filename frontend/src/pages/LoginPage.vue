<template>
    <AuthLayout>
        <div class="space-y-8">
            <div class="space-y-4">
                <p class="text-xs uppercase tracking-[0.3em] text-amber-300/80">{{ t('app.name') }}</p>
                <h1 class="text-3xl font-semibold text-white">{{ t('auth.welcome_title') }}</h1>
                <p class="text-base text-stone-300">{{ t('auth.welcome_body') }}</p>
            </div>

            <form class="space-y-5" @submit.prevent="onSubmit">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-stone-200" for="login">
                        {{ t('auth.form.login_label') }}
                    </label>
                    <input
                        id="login"
                        v-model="loginValue"
                        type="text"
                        autocomplete="username"
                        class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none transition focus:border-amber-300/40"
                        :placeholder="t('auth.form.login_placeholder')"
                    />
                    <p v-if="errors.login" class="text-sm text-rose-300">{{ errors.login }}</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-stone-200" for="password">
                        {{ t('auth.form.password_label') }}
                    </label>
                    <input
                        id="password"
                        v-model="passwordValue"
                        type="password"
                        autocomplete="current-password"
                        class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none transition focus:border-amber-300/40"
                        :placeholder="t('auth.form.password_placeholder')"
                    />
                    <p v-if="errors.password" class="text-sm text-rose-300">{{ errors.password }}</p>
                </div>

                <p v-if="submitError !== null" class="text-sm text-rose-300">{{ submitError }}</p>

                <button
                    type="submit"
                    class="w-full rounded-2xl bg-amber-400 px-5 py-3 text-sm font-semibold text-stone-950 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="isSubmitting"
                >
                    {{ isSubmitting ? t('auth.form.submitting') : t('auth.form.submit') }}
                </button>
            </form>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from 'vee-validate';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import type { LoginInput } from '@/api/auth';
import { loginSchema } from '@/api/auth';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { useAuthStore } from '@/stores/auth';

const { t } = useI18n();
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
        await router.push({ name: 'dashboard' });
    } catch {
        const message = t('auth.form.invalid_credentials');

        submitError.value = message;
        setErrors({
            login: message,
        });
    }
});
</script>
