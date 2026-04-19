<template>
    <div class="min-h-screen px-6 py-8 text-stone-50">
        <div class="mx-auto flex max-w-6xl flex-col gap-8">
            <header class="flex flex-wrap items-center justify-between gap-4 rounded-[2rem] border border-white/10 bg-white/5 px-6 py-5 backdrop-blur">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-amber-300/80">{{ t('app.name') }}</p>
                    <h1 class="text-2xl font-semibold text-white">{{ t('navigation.dashboard') }}</h1>
                </div>
                <nav class="flex flex-wrap gap-2 text-sm text-stone-200">
                    <RouterLink
                        v-for="item in navigationItems"
                        :key="item.name"
                        :to="item.to"
                        class="rounded-full border border-white/10 px-4 py-2 transition hover:border-amber-300/40 hover:text-amber-100"
                    >
                        {{ t(item.name) }}
                    </RouterLink>
                </nav>
            </header>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';

const { t } = useI18n();

const navigationItems = [
    { name: 'navigation.dashboard', to: { name: 'dashboard' } },
    { name: 'navigation.customers', to: { name: 'dashboard' } },
    { name: 'navigation.debts', to: { name: 'dashboard' } },
    { name: 'navigation.settings', to: { name: 'login' } },
];
</script>
