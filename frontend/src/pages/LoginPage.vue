<template>
    <AuthLayout>
        <div class="space-y-4">
            <p class="text-xs uppercase tracking-[0.3em] text-amber-300/80">{{ t('app.name') }}</p>
            <h1 class="text-3xl font-semibold text-white">{{ t('auth.welcome_title') }}</h1>
            <p class="text-base text-stone-300">{{ t('auth.welcome_body') }}</p>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import AuthLayout from '@/layouts/AuthLayout.vue';

const { t } = useI18n();
</script>
