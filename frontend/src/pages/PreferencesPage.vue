<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-panel lex-panel-header p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="lex-page-eyebrow">{{ t('preferences.eyebrow') }}</p>
                        <h1 class="lex-page-title">{{ t('preferences.title') }}</h1>
                    </div>

                    <RouterLink :to="{ name: 'profile' }" class="lex-button lex-button-secondary">
                        {{ t('navigation.profile') }}
                    </RouterLink>
                </div>
            </section>

            <UserPreferencesSection
                :profile="profile"
                :districts="districts"
                :title="t('preferences.form_title')"
                :intro="t('preferences.form_intro')"
                @saved="onPreferencesSaved"
            />
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import { fetchAccessibleDistricts, type DistrictSummary } from '@/api/districts';
import type { UserProfile } from '@/api/users';
import { fetchUserProfile } from '@/api/users';
import UserPreferencesSection from '@/components/domain/UserPreferencesSection.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();
const profile = ref<UserProfile | null>(null);
const districts = ref<DistrictSummary[]>([]);

async function loadProfile(): Promise<void> {
    if (authStore.user?.ulid === null || authStore.user?.ulid === undefined) {
        return;
    }

    const [loadedProfile, loadedDistricts] = await Promise.all([
        fetchUserProfile(authStore.user.ulid),
        fetchAccessibleDistricts(),
    ]);

    profile.value = loadedProfile;
    districts.value = loadedDistricts;
}

function onPreferencesSaved(updatedProfile: UserProfile): void {
    profile.value = updatedProfile;
}

onMounted(async () => {
    await loadProfile();
});
</script>
