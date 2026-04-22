<template>
    <div class="lex-app-shell">
        <header class="lex-app-header">
            <div class="lex-app-header-inner">
                <div class="lex-app-nav-row">
                    <RouterLink :to="{ name: 'dashboard' }" class="lex-brand">
                        {{ t('app.name') }}
                    </RouterLink>

                    <div class="lex-app-account">
                        <div class="lex-app-account-copy" v-if="displayName !== ''">
                            <p class="lex-app-account-label">{{ t('app.signed_in_as') }}</p>
                            <p class="lex-app-account-name">{{ displayName }}</p>
                        </div>
                        <div class="lex-app-account-actions">
                            <RouterLink :to="{ name: 'dashboard' }" class="lex-button lex-button-secondary">
                                {{ t('navigation.dashboard') }}
                            </RouterLink>
                            <a v-if="showMailpit" href="/mail/" class="lex-button lex-button-secondary">
                                {{ t('app.mailpit') }}
                            </a>
                            <button type="button" class="lex-button lex-button-secondary" @click="onSignOut">
                                {{ t('app.logout') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="lex-app-breadcrumb-bar">
            <div class="lex-app-breadcrumb-inner">
                <ol class="lex-app-breadcrumb-list">
                    <li>
                        <RouterLink :to="{ name: 'dashboard' }" class="lex-app-breadcrumb-link">
                            {{ t('navigation.dashboard') }}
                        </RouterLink>
                    </li>
                    <li v-if="route.name === 'district'">/</li>
                    <li v-if="route.name === 'district'" class="lex-app-breadcrumb-current">
                        {{ route.params.district }}
                    </li>
                </ol>
            </div>
        </div>

        <main class="lex-app-main">
            <section class="lex-app-content">
                <slot />
            </section>
        </main>

        <footer class="lex-app-footer">
            <div class="lex-app-footer-inner">
                <span>{{ t('app.footer') }}</span>
                <span v-if="showMailpit">{{ t('app.mailpit_hint') }}</span>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const showMailpit = import.meta.env.DEV;

const displayName = computed(() => [authStore.user?.name, authStore.user?.surname].filter((value): value is string => typeof value === 'string' && value !== '').join(' '));

async function onSignOut(): Promise<void> {
    await authStore.signOut();
    await router.push({ name: 'login' });
}
</script>
