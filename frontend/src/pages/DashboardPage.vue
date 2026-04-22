<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-overview-hero-dark">
                <div class="lex-overview-hero-body">
                    <div>
                        <p class="lex-overview-eyebrow">{{ t('dashboard.eyebrow') }}</p>
                        <h1 class="lex-overview-title">{{ heroTitle }}</h1>
                        <p class="lex-overview-copy">{{ heroCopy }}</p>
                    </div>

                    <div class="lex-overview-metric-grid-dark">
                        <article v-for="item in heroStats" :key="item.label" class="lex-overview-metric-card-dark">
                            <p class="lex-overview-metric-label">{{ t(item.label) }}</p>
                            <p class="lex-overview-metric-value">{{ item.value }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="lex-overview-hero-split">
                <article class="lex-overview-hero">
                    <div class="lex-overview-hero-accent" />
                    <div class="p-8">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="lex-pill-accent">{{ t('dashboard.focus_badge') }}</span>
                            <span class="lex-pill">{{ t('dashboard.access_badge') }}</span>
                        </div>

                        <h2 class="mt-5 text-3xl font-semibold text-[var(--lex-text)]">
                            {{ t('dashboard.focus_title') }}
                        </h2>
                        <p class="mt-3 text-[var(--lex-muted)] leading-7">
                            {{ t('dashboard.focus_copy') }}
                        </p>

                        <div class="lex-overview-metric-grid mt-6">
                            <article v-for="item in focusStats" :key="item.label" class="lex-overview-metric-card">
                                <p class="lex-overview-metric-label">{{ t(item.label) }}</p>
                                <p class="lex-overview-metric-value">{{ item.value }}</p>
                            </article>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <RouterLink :to="{ name: 'dashboard' }" class="lex-button lex-button-primary">
                                {{ t('dashboard.primary_action') }}
                            </RouterLink>
                            <a v-if="showMailpit" href="/mail/" class="lex-button lex-button-secondary">
                                {{ t('dashboard.secondary_action') }}
                            </a>
                        </div>
                    </div>
                </article>

                <article class="lex-panel p-8">
                    <p class="lex-overview-support-title">{{ t('dashboard.quick_actions') }}</p>
                    <div class="mt-5 grid gap-3">
                        <RouterLink :to="{ name: 'dashboard' }" class="lex-button lex-button-primary">
                            {{ t('dashboard.open_dashboard') }}
                        </RouterLink>
                        <a v-if="showMailpit" href="/mail/" class="lex-button lex-button-secondary">
                            {{ t('dashboard.open_mailpit') }}
                        </a>
                    </div>
                </article>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <article class="lex-overview-support-card">
                    <p class="lex-overview-support-title">{{ t('dashboard.environment_title') }}</p>
                    <p class="lex-overview-support-value">{{ t('dashboard.environment_value') }}</p>
                    <p class="lex-overview-support-copy">{{ t('dashboard.environment_copy') }}</p>
                </article>

                <article class="lex-overview-support-card">
                    <p class="lex-overview-support-title">{{ t('dashboard.today_title') }}</p>
                    <p class="lex-overview-support-value">{{ t('dashboard.today_value') }}</p>
                    <p class="lex-overview-support-copy">{{ t('dashboard.today_copy') }}</p>
                </article>

                <article class="lex-overview-support-card">
                    <p class="lex-overview-support-title">{{ t('dashboard.access_title') }}</p>
                    <p class="lex-overview-support-value">{{ t('dashboard.access_value') }}</p>
                    <p class="lex-overview-support-copy">{{ t('dashboard.access_copy') }}</p>
                </article>
            </section>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();
const showMailpit = import.meta.env.DEV;

const firstName = computed(() => authStore.user?.name?.split(' ')[0] ?? '');
const heroTitle = computed(() => firstName.value !== '' ? t('dashboard.greeting', { name: firstName.value }) : t('dashboard.fallback'));
const heroCopy = computed(() => t('dashboard.focus_copy'));

const heroStats = [
    { label: 'dashboard.stats_districts', value: '1' },
    { label: 'dashboard.stats_customers', value: '0' },
    { label: 'dashboard.stats_debts', value: '0' },
    { label: 'dashboard.stats_payments', value: '0' },
];

const focusStats = [
    { label: 'dashboard.stats_customers', value: '0' },
    { label: 'dashboard.stats_debts', value: '0' },
];
</script>
