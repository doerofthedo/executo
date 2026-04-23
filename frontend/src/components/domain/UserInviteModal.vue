<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-8"
        @click.self="$emit('close')"
    >
        <section class="w-full max-w-xl rounded-[1.75rem] bg-white p-8 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-2">
                    <p class="lex-page-eyebrow">{{ t('user_management.invite_eyebrow') }}</p>
                    <h2 class="lex-page-title text-[1.5rem]">{{ t('user_management.invite_title') }}</h2>
                    <p class="lex-page-copy lex-page-copy-full">{{ t('user_management.invite_intro', { district: districtName }) }}</p>
                </div>
                <button type="button" class="lex-button lex-button-secondary" @click="$emit('close')">
                    {{ t('user_management.close') }}
                </button>
            </div>

            <form class="mt-6 space-y-5" @submit.prevent="onSubmit">
                <label class="lex-form-field">
                    <span class="lex-input-label">{{ t('operations.email') }}</span>
                    <input v-model="form.email" type="email" class="lex-input" />
                    <p v-if="emailError !== ''" class="lex-input-error-message">{{ emailError }}</p>
                </label>

                <label class="lex-form-field">
                    <span class="lex-input-label">{{ t('operations.role') }}</span>
                    <UserRoleSelect v-model="form.role" :allowed-roles="allowedRoles" />
                </label>

                <p v-if="submitError !== ''" class="lex-form-message lex-form-message-error">{{ submitError }}</p>

                <div class="flex flex-wrap justify-end gap-3">
                    <button type="button" class="lex-button lex-button-secondary" @click="$emit('close')">
                        {{ t('user_management.cancel') }}
                    </button>
                    <button type="submit" class="lex-button lex-button-primary" :disabled="submitting">
                        {{ submitting ? t('operations.saving') : t('user_management.send_invite') }}
                    </button>
                </div>
            </form>
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { createDistrictUserSchema, type DistrictUserFormInput } from '@/api/users';
import UserRoleSelect from '@/components/domain/UserRoleSelect.vue';

const props = withDefaults(defineProps<{
    open: boolean;
    districtName: string;
    allowedRoles?: Array<'district.admin' | 'district.manager' | 'district.user'>;
    submitting?: boolean;
    submitError?: string;
}>(), {
    allowedRoles: () => ['district.admin', 'district.manager', 'district.user'],
    submitting: false,
    submitError: '',
});

const emit = defineEmits<{
    close: [];
    submit: [payload: DistrictUserFormInput];
}>();

const { t } = useI18n();
const form = ref<DistrictUserFormInput>({
    email: '',
    role: 'district.user',
});
const emailError = ref('');

const normalizedOpen = computed(() => props.open);

watch(normalizedOpen, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.value = {
        email: '',
        role: props.allowedRoles.includes('district.user') ? 'district.user' : props.allowedRoles[0],
    };
    emailError.value = '';
});

function onSubmit(): void {
    emailError.value = '';

    const parsed = createDistrictUserSchema(t).safeParse(form.value);

    if (!parsed.success) {
        emailError.value = parsed.error.issues.find((issue) => issue.path[0] === 'email')?.message ?? '';
        return;
    }

    emit('submit', parsed.data);
}
</script>
