<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-panel lex-panel-header p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="lex-page-eyebrow">{{ t('profile.eyebrow') }}</p>
                        <h1 class="lex-page-title">{{ displayName }}</h1>
                    </div>

                    <RouterLink :to="{ name: 'preferences' }" class="lex-button lex-button-secondary">
                        {{ t('preferences.title') }}
                    </RouterLink>
                </div>

                <p v-if="showVerificationRequiredNotice" class="lex-form-message lex-form-message-error mt-4">
                    {{ t('profile.verification_required') }}
                </p>
                <p v-if="showVerificationSuccessNotice" class="lex-form-message lex-form-message-success mt-4">
                    {{ t('profile.verification_confirmed') }}
                </p>
            </section>

            <section class="lex-panel p-8">
                <h2 class="lex-heading-lg">{{ t('profile.identity_title') }}</h2>
                <p class="lex-page-copy lex-page-copy-full">{{ t('profile.identity_intro') }}</p>

                <dl class="lex-identity-grid">
                    <div class="lex-identity-item">
                        <dt class="lex-key-value-label">{{ t('profile.name') }}</dt>
                        <dd class="lex-key-value-value">{{ profile?.name ?? authStore.user?.name ?? '—' }}</dd>
                    </div>
                    <div class="lex-identity-item">
                        <dt class="lex-key-value-label">{{ t('profile.surname') }}</dt>
                        <dd class="lex-key-value-value">{{ profile?.surname ?? authStore.user?.surname ?? '—' }}</dd>
                    </div>
                    <div class="lex-identity-item">
                        <dt class="lex-key-value-label">{{ t('profile.current_email') }}</dt>
                        <dd class="lex-key-value-value lex-break-anywhere">{{ profile?.email ?? authStore.user?.email ?? '—' }}</dd>
                    </div>
                    <div class="lex-identity-item">
                        <dt class="lex-key-value-label">{{ t('profile.verification_status') }}</dt>
                        <dd class="lex-key-value-value">
                            <span class="lex-pill" :class="{ 'lex-pill--success': isVerified }">
                                {{ isVerified ? t('profile.verified') : t('profile.not_verified') }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="lex-panel p-8">
                <h2 class="lex-heading-lg">{{ t('profile.edit_name_title') }}</h2>
                <p class="lex-page-copy lex-page-copy-full">{{ t('profile.edit_name_intro') }}</p>

                <form class="lex-form" @submit.prevent="onNameSubmit">
                    <div class="lex-settings-grid">
                        <label class="lex-form-field">
                            <span class="lex-input-label">{{ t('profile.name') }}</span>
                            <input v-model="nameValue" type="text" class="lex-input" :disabled="isNameSubmitting || profile === null" />
                            <p v-if="nameErrors.name" class="lex-input-error-message">{{ nameErrors.name }}</p>
                        </label>
                        <label class="lex-form-field">
                            <span class="lex-input-label">{{ t('profile.surname') }}</span>
                            <input v-model="surnameValue" type="text" class="lex-input" :disabled="isNameSubmitting || profile === null" />
                            <p v-if="nameErrors.surname" class="lex-input-error-message">{{ nameErrors.surname }}</p>
                        </label>
                    </div>

                    <div class="lex-section-actions">
                        <button type="submit" class="lex-button lex-button-primary" :disabled="isNameSubmitting || profile === null">
                            {{ isNameSubmitting ? t('profile.working') : t('profile.save_name') }}
                        </button>
                        <p v-if="nameInfo !== ''" class="lex-form-message lex-form-message-success">{{ nameInfo }}</p>
                        <p v-if="nameError !== ''" class="lex-form-message lex-form-message-error">{{ nameError }}</p>
                    </div>
                </form>
            </section>

            <div class="lex-overview-hero-split">
                <section class="lex-panel p-8">
                    <h2 class="lex-heading-lg">{{ t('profile.verify_email_title') }}</h2>
                    <p class="lex-page-copy lex-page-copy-full">{{ t('profile.verify_email_intro') }}</p>

                    <div class="lex-section-actions">
                        <button type="button" class="lex-button lex-button-primary" :disabled="isVerificationSubmitting || profile?.email === null" @click="sendVerificationEmail">
                            {{ isVerificationSubmitting ? t('profile.working') : t('profile.send_verification') }}
                        </button>
                    </div>

                    <p v-if="verificationInfo !== ''" class="lex-form-message lex-form-message-success">{{ verificationInfo }}</p>
                    <p v-if="verificationError !== ''" class="lex-form-message lex-form-message-error">{{ verificationError }}</p>
                </section>

                <section class="lex-panel p-8">
                    <h2 class="lex-heading-lg">{{ t('profile.password_reset_title') }}</h2>
                    <p class="lex-page-copy lex-page-copy-full">{{ t('profile.password_reset_intro') }}</p>

                    <div class="lex-section-actions">
                        <button type="button" class="lex-button lex-button-secondary" :disabled="isPasswordResetSubmitting || profile?.email === null" @click="requestPasswordReset">
                            {{ isPasswordResetSubmitting ? t('profile.working') : t('profile.send_password_reset') }}
                        </button>
                    </div>

                    <p v-if="passwordResetInfo !== ''" class="lex-form-message lex-form-message-success">{{ passwordResetInfo }}</p>
                    <p v-if="passwordResetError !== ''" class="lex-form-message lex-form-message-error">{{ passwordResetError }}</p>
                </section>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { z } from 'zod';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';
import { requestEmailVerification, forgotPassword } from '@/api/auth';
import { fetchUserProfile, updateUserProfile, type UserProfile } from '@/api/users';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();
const route = useRoute();

const profile = ref<UserProfile | null>(null);
const nameInfo = ref('');
const nameError = ref('');
const verificationInfo = ref('');
const verificationError = ref('');
const passwordResetInfo = ref('');
const passwordResetError = ref('');
const isVerificationSubmitting = ref(false);
const isPasswordResetSubmitting = ref(false);

const nameSchema = computed(() => toTypedSchema(z.object({
    name: z.string().trim().min(1, t('auth.validation.field_required')),
    surname: z.string().trim().min(1, t('auth.validation.field_required')),
})));

const {
    errors: nameErrors,
    defineField: defineNameField,
    handleSubmit: handleNameSubmit,
    isSubmitting: isNameSubmitting,
    setValues: setNameValues,
} = useForm<{ name: string; surname: string }>({
    validationSchema: nameSchema,
    initialValues: { name: '', surname: '' },
});

const [nameValue] = defineNameField('name');
const [surnameValue] = defineNameField('surname');

const displayName = computed(() => {
    const name = profile.value?.name ?? authStore.user?.name ?? '';
    const surname = profile.value?.surname ?? authStore.user?.surname ?? '';

    return [name, surname].filter((value): value is string => value !== null && value !== '').join(' ') || t('profile.fallback_name');
});

const isVerified = computed(() => profile.value?.is_email_verified ?? authStore.user?.is_email_verified ?? false);
const showVerificationRequiredNotice = computed(() => route.query.verification === 'required' && !isVerified.value);
const showVerificationSuccessNotice = computed(() => route.query.verification === 'success' && isVerified.value);

watch(profile, (p) => {
    if (p === null) return;
    setNameValues({ name: p.name ?? '', surname: p.surname ?? '' });
});

async function loadProfile(): Promise<void> {
    if (authStore.user?.ulid === null || authStore.user?.ulid === undefined) {
        return;
    }

    profile.value = await fetchUserProfile(authStore.user.ulid);
}

const onNameSubmit = handleNameSubmit(async (values) => {
    if (profile.value === null) return;

    nameInfo.value = '';
    nameError.value = '';

    try {
        const updated = await updateUserProfile(profile.value.ulid, {
            name: values.name,
            surname: values.surname,
        });

        profile.value = updated;

        if (authStore.user !== null) {
            authStore.user.name = updated.name;
            authStore.user.surname = updated.surname;
        }

        nameInfo.value = t('profile.name_saved');
    } catch {
        nameError.value = t('profile.name_save_error');
    }
});

async function sendVerificationEmail(): Promise<void> {
    if (profile.value?.email === null || profile.value?.email === undefined) {
        return;
    }

    verificationInfo.value = '';
    verificationError.value = '';
    isVerificationSubmitting.value = true;

    try {
        await requestEmailVerification(profile.value.email);
        verificationInfo.value = t('profile.verification_sent');
    } catch {
        verificationError.value = t('profile.verification_error');
    } finally {
        isVerificationSubmitting.value = false;
    }
}

async function requestPasswordReset(): Promise<void> {
    if (profile.value?.email === null || profile.value?.email === undefined) {
        return;
    }

    passwordResetInfo.value = '';
    passwordResetError.value = '';
    isPasswordResetSubmitting.value = true;

    try {
        await forgotPassword({ email: profile.value.email });
        passwordResetInfo.value = t('profile.password_reset_sent');
    } catch {
        passwordResetError.value = t('profile.password_reset_error');
    } finally {
        isPasswordResetSubmitting.value = false;
    }
}

onMounted(async () => {
    await loadProfile();
});
</script>
