<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-overview-hero">
                <div class="lex-overview-hero-accent" />
                <div class="lex-overview-hero-body lex-overview-hero-grid">
                    <div class="lex-overview-hero-main">
                        <p class="lex-overview-eyebrow">{{ t('profile.eyebrow') }}</p>
                        <h1 class="lex-overview-title">{{ displayName }}</h1>
                        <p class="lex-overview-copy">{{ t('profile.intro') }}</p>

                        <div class="lex-overview-metric-grid">
                            <article class="lex-overview-metric-card">
                                <p class="lex-overview-metric-label">{{ t('profile.current_email') }}</p>
                                <p class="lex-overview-support-value lex-break-anywhere">{{ profile?.email ?? authStore.user?.email ?? '—' }}</p>
                            </article>
                            <article class="lex-overview-metric-card">
                                <p class="lex-overview-metric-label">{{ t('profile.verification_status') }}</p>
                                <p class="lex-overview-support-value">
                                    {{ isVerified ? t('profile.verified') : t('profile.not_verified') }}
                                </p>
                            </article>
                            <article class="lex-overview-metric-card">
                                <p class="lex-overview-metric-label">{{ t('preferences.language') }}</p>
                                <p class="lex-overview-support-value">
                                    {{ activeLocale === 'en' ? t('preferences.locale_en') : t('preferences.locale_lv') }}
                                </p>
                            </article>
                        </div>
                    </div>

                    <div class="lex-overview-hero-sidebar">
                        <article class="lex-overview-support-card">
                            <p class="lex-overview-support-title">{{ t('profile.security_title') }}</p>
                            <p class="lex-overview-support-copy">{{ t('profile.security_copy') }}</p>
                        </article>
                        <article class="lex-overview-support-card">
                            <p class="lex-overview-support-title">{{ t('preferences.title') }}</p>
                            <p class="lex-overview-support-copy">{{ t('preferences.body') }}</p>
                            <RouterLink :to="{ name: 'preferences' }" class="lex-button lex-button-secondary lex-inline-action">
                                {{ t('preferences.open') }}
                            </RouterLink>
                        </article>
                    </div>
                </div>
            </section>

            <section class="lex-overview-hero-split">
                <article class="lex-panel p-8">
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
                            <dd class="lex-key-value-value">{{ isVerified ? t('profile.verified') : t('profile.not_verified') }}</dd>
                        </div>
                    </dl>
                </article>

                <article class="lex-panel p-8">
                    <h2 class="lex-heading-lg">{{ t('profile.verify_email_title') }}</h2>
                    <p class="lex-page-copy lex-page-copy-full">{{ t('profile.verify_email_intro') }}</p>

                    <div class="lex-section-actions">
                        <button type="button" class="lex-button lex-button-primary" :disabled="isVerificationSubmitting || profile?.email === null" @click="sendVerificationEmail">
                            {{ isVerificationSubmitting ? t('profile.working') : t('profile.send_verification') }}
                        </button>
                    </div>

                    <p v-if="verificationInfo !== ''" class="lex-form-message lex-form-message-success">{{ verificationInfo }}</p>
                    <p v-if="verificationError !== ''" class="lex-form-message lex-form-message-error">{{ verificationError }}</p>
                </article>
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
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import { requestEmailVerification, forgotPassword } from '@/api/auth';
import { fetchUserProfile, type UserProfile } from '@/api/users';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();

const profile = ref<UserProfile | null>(null);
const verificationInfo = ref('');
const verificationError = ref('');
const passwordResetInfo = ref('');
const passwordResetError = ref('');
const isVerificationSubmitting = ref(false);
const isPasswordResetSubmitting = ref(false);

const displayName = computed(() => {
    const name = profile.value?.name ?? authStore.user?.name ?? '';
    const surname = profile.value?.surname ?? authStore.user?.surname ?? '';

    return [name, surname].filter((value): value is string => value !== null && value !== '').join(' ') || t('profile.fallback_name');
});

const isVerified = computed(() => profile.value?.is_email_verified ?? authStore.user?.is_email_verified ?? false);
const activeLocale = computed(() => profile.value?.preferences.locale === 'en' ? 'en' : 'lv');

async function loadProfile(): Promise<void> {
    if (authStore.user?.ulid === null || authStore.user?.ulid === undefined) {
        return;
    }

    profile.value = await fetchUserProfile(authStore.user.ulid);
}

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
