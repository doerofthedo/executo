<template>
    <AppLayout>
        <section class="grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
            <div class="rounded-[2rem] border border-white/10 bg-black/25 p-8">
                <p class="text-sm uppercase tracking-[0.3em] text-amber-300/80">{{ t('dashboard.eyebrow') }}</p>
                <h2 class="mt-4 max-w-2xl text-4xl font-semibold leading-tight text-white">
                    {{ t('dashboard.title') }}
                </h2>
                <p class="mt-4 max-w-xl text-base text-stone-300">
                    {{ t('dashboard.subtitle') }}
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <button class="rounded-full bg-amber-400 px-5 py-3 text-sm font-semibold text-stone-950">
                        {{ t('dashboard.primary_action') }}
                    </button>
                    <button class="rounded-full border border-white/15 px-5 py-3 text-sm font-semibold text-white">
                        {{ t('dashboard.secondary_action') }}
                    </button>
                </div>
            </div>

            <div class="grid gap-4">
                <article
                    v-for="item in stats"
                    :key="item.label"
                    class="rounded-[1.5rem] border border-white/10 bg-white/5 p-5"
                >
                    <p class="text-sm text-stone-300">{{ t(item.label) }}</p>
                    <p class="mt-3 text-3xl font-semibold text-white">{{ item.value }}</p>
                </article>
            </div>
        </section>
    </AppLayout>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';

const { t } = useI18n();

const stats = [
    { label: 'dashboard.stats_districts', value: '120' },
    { label: 'dashboard.stats_customers', value: '0' },
    { label: 'dashboard.stats_debts', value: '0' },
];
</script>
