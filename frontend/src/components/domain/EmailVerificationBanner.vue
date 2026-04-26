<template>
    <div v-if="visible" class="lex-panel p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="lex-form-message lex-form-message-error m-0">
                {{ t('profile.email_verification_pending') }}
            </p>
            <div class="flex gap-3">
                <RouterLink :to="{ name: 'profile' }" class="lex-button lex-button-primary">
                    {{ t('profile.verify_email_button') }}
                </RouterLink>
                <button type="button" class="lex-button lex-button-secondary" @click="dismiss">
                    {{ t('profile.dismiss_button') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const STORAGE_KEY = 'emailVerificationBannerDismissed';

const { t } = useI18n();
const authStore = useAuthStore();
const dismissed = ref(sessionStorage.getItem(STORAGE_KEY) === 'true');

const visible = computed(() => authStore.user?.is_email_verified === false && !dismissed.value);

function dismiss(): void {
    sessionStorage.setItem(STORAGE_KEY, 'true');
    dismissed.value = true;
}
</script>
