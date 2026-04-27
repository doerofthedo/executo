<template>
    <section class="lex-panel p-8">
        <div v-if="title !== '' || intro !== ''" class="lex-section-header">
            <h2 v-if="title !== ''" class="lex-heading-lg">{{ title }}</h2>
            <p v-if="intro !== ''" class="lex-page-copy lex-page-copy-full">{{ intro }}</p>
        </div>

        <form class="lex-form" @submit.prevent="onSubmit">
            <div class="lex-settings-grid">
                <label class="lex-form-field">
                    <span class="lex-input-label">{{ t('preferences.default_district') }}</span>
                    <select v-model="defaultDistrictUlidValue" class="lex-input">
                        <option :value="null">{{ t('preferences.default_district_none') }}</option>
                        <option v-for="district in districts" :key="district.ulid" :value="district.ulid">
                            {{ t('preferences.default_district_option', { number: district.number, name: districtName(district) }) }}
                        </option>
                    </select>
                    <p v-if="errors.default_district_ulid" class="lex-input-error-message">{{ errors.default_district_ulid }}</p>
                </label>

                <label class="lex-form-field">
                    <span class="lex-input-label">{{ t('preferences.language') }}</span>
                    <select v-model="localeValue" class="lex-input">
                        <option value="lv">{{ t('preferences.locale_lv') }}</option>
                        <option value="en">{{ t('preferences.locale_en') }}</option>
                    </select>
                    <p v-if="errors.locale" class="lex-input-error-message">{{ errors.locale }}</p>
                </label>

                <label class="lex-form-field">
                    <span class="lex-input-label">{{ t('preferences.timezone') }}</span>
                    <select v-model="timezoneValue" class="lex-input">
                        <option value="Europe/Riga">{{ t('preferences.timezone_europe_riga') }}</option>
                        <option value="UTC">{{ t('preferences.timezone_utc') }}</option>
                    </select>
                    <p v-if="errors.timezone" class="lex-input-error-message">{{ errors.timezone }}</p>
                </label>

                <label class="lex-form-field">
                    <span class="lex-input-label">{{ t('preferences.date_format') }}</span>
                    <select v-model="dateFormatValue" class="lex-input">
                        <option v-for="option in dateFormatOptions" :key="option" :value="option">{{ option }}</option>
                    </select>
                    <p v-if="errors.date_format" class="lex-input-error-message">{{ errors.date_format }}</p>
                </label>

                <label class="lex-form-field">
                    <span class="lex-input-label">{{ t('preferences.decimal_separator') }}</span>
                    <select v-model="decimalSeparatorValue" class="lex-input">
                        <option value=",">{{ t('preferences.decimal_separator_comma') }}</option>
                        <option value=".">{{ t('preferences.decimal_separator_dot') }}</option>
                    </select>
                    <p v-if="errors.decimal_separator" class="lex-input-error-message">{{ errors.decimal_separator }}</p>
                </label>

                <label class="lex-form-field">
                    <span class="lex-input-label">{{ t('preferences.thousand_separator') }}</span>
                    <select v-model="thousandSeparatorValue" class="lex-input">
                        <option value=" ">{{ t('preferences.thousand_separator_space') }}</option>
                        <option value=".">{{ t('preferences.thousand_separator_dot') }}</option>
                        <option value=",">{{ t('preferences.thousand_separator_comma') }}</option>
                        <option value="'">{{ t('preferences.thousand_separator_apostrophe') }}</option>
                    </select>
                    <p v-if="errors.thousand_separator" class="lex-input-error-message">{{ errors.thousand_separator }}</p>
                </label>

                <label class="lex-form-field">
                    <span class="lex-input-label">{{ t('preferences.table_page_size') }}</span>
                    <select v-model="tablePageSizeValue" class="lex-input">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                    <p v-if="errors.table_page_size" class="lex-input-error-message">{{ errors.table_page_size }}</p>
                </label>
            </div>

            <div class="lex-section-actions">
                <button type="submit" class="lex-button lex-button-primary" :disabled="isSubmitting || profile === null">
                    {{ isSubmitting ? t('preferences.saving') : t('preferences.save') }}
                </button>
                <p v-if="savedMessage !== ''" class="lex-form-message lex-form-message-success">{{ savedMessage }}</p>
                <p v-if="formError !== ''" class="lex-form-message lex-form-message-error">{{ formError }}</p>
            </div>
        </form>
    </section>
</template>

<script setup lang="ts">
import { useForm } from 'vee-validate';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import type { DistrictSummary } from '@/api/districts';
import { useAuthStore } from '@/stores/auth';
import { usePreferencesStore } from '@/stores/preferences';
import type { UserProfile, UserPreferencesInput } from '@/api/users';
import { createUserPreferencesSchema, updateUserPreferences } from '@/api/users';
import { isApiError } from '@/api/client';
import { toTypedSchema } from '@vee-validate/zod';

const props = withDefaults(defineProps<{
    profile: UserProfile | null;
    districts: DistrictSummary[];
    title?: string;
    intro?: string;
}>(), {
    title: '',
    intro: '',
});

const emit = defineEmits<{
    saved: [profile: UserProfile];
}>();

const { t, locale } = useI18n();
const authStore = useAuthStore();
const preferencesStore = usePreferencesStore();
const savedMessage = ref('');
const formError = ref('');
const dateFormatOptions = ['DD.MM.YYYY.', 'DD.MM.YYYY', 'DD-MM-YYYY', 'DD-MMM-YYYY', 'YYYY-MM-DD'];

function normalizeTimezone(value: string | null): UserPreferencesInput['timezone'] {
    return value === 'UTC' ? 'UTC' : 'Europe/Riga';
}

function normalizeDecimalSeparator(value: string | null): UserPreferencesInput['decimal_separator'] {
    return value === '.' ? '.' : ',';
}

function normalizeThousandSeparator(value: string | null): UserPreferencesInput['thousand_separator'] {
    return value === '.' || value === ',' || value === "'" ? value : ' ';
}

const preferencesSchema = computed(() => toTypedSchema(createUserPreferencesSchema(t)));

const { errors, defineField, handleSubmit, isSubmitting, resetForm } = useForm<UserPreferencesInput>({
    validationSchema: preferencesSchema,
    initialValues: {
        default_district_ulid: null,
        locale: 'lv',
        timezone: 'Europe/Riga',
        date_format: 'DD.MM.YYYY.',
        decimal_separator: ',',
        thousand_separator: ' ',
        table_page_size: 25,
    },
});

const [defaultDistrictUlidValue] = defineField('default_district_ulid');
const [localeValue] = defineField('locale');
const [timezoneValue] = defineField('timezone');
const [dateFormatValue] = defineField('date_format');
const [decimalSeparatorValue] = defineField('decimal_separator');
const [thousandSeparatorValue] = defineField('thousand_separator');
const [tablePageSizeValue] = defineField('table_page_size');

watch(
    () => props.profile,
    (profile) => {
        if (profile === null) {
            return;
        }

        resetForm({
            values: {
                default_district_ulid: profile.preferences.default_district_ulid,
                locale: (profile.preferences.locale === 'en' ? 'en' : 'lv'),
                timezone: normalizeTimezone(profile.preferences.timezone),
                date_format: profile.preferences.date_format ?? 'DD.MM.YYYY.',
                decimal_separator: normalizeDecimalSeparator(profile.preferences.decimal_separator),
                thousand_separator: normalizeThousandSeparator(profile.preferences.thousand_separator),
                table_page_size: profile.preferences.table_page_size ?? 25,
            },
        });
    },
    { immediate: true },
);

const onSubmit = handleSubmit(async (values) => {
    if (props.profile === null) {
        return;
    }

    savedMessage.value = '';
    formError.value = '';
    const currentValues: UserPreferencesInput = {
        default_district_ulid: props.profile.preferences.default_district_ulid,
        locale: (props.profile.preferences.locale === 'en' ? 'en' : 'lv'),
        timezone: normalizeTimezone(props.profile.preferences.timezone),
        date_format: props.profile.preferences.date_format ?? 'DD.MM.YYYY.',
        decimal_separator: normalizeDecimalSeparator(props.profile.preferences.decimal_separator),
        thousand_separator: normalizeThousandSeparator(props.profile.preferences.thousand_separator),
        table_page_size: props.profile.preferences.table_page_size ?? 25,
    };

    if (JSON.stringify(values) === JSON.stringify(currentValues)) {
        savedMessage.value = t('preferences.no_changes');
        return;
    }

    try {
        const updatedProfile = await updateUserPreferences(props.profile.ulid, {
            ...values,
            default_district_ulid: values.default_district_ulid === '' ? null : values.default_district_ulid,
        });

        preferencesStore.locale = updatedProfile.preferences.locale === 'en' ? 'en' : 'lv';
        preferencesStore.timezone = updatedProfile.preferences.timezone ?? 'Europe/Riga';
        preferencesStore.dateFormat = updatedProfile.preferences.date_format ?? 'DD.MM.YYYY.';
        preferencesStore.decimalSeparator = updatedProfile.preferences.decimal_separator ?? ',';
        preferencesStore.thousandSeparator = updatedProfile.preferences.thousand_separator ?? ' ';
        preferencesStore.tablePageSize = updatedProfile.preferences.table_page_size ?? 25;
        if (authStore.user !== null) {
            authStore.user.default_district_ulid = updatedProfile.preferences.default_district_ulid;
            authStore.user.timezone = updatedProfile.preferences.timezone ?? 'Europe/Riga';
        }

        locale.value = preferencesStore.locale;
        savedMessage.value = t('preferences.saved');
        emit('saved', updatedProfile);
    } catch (error) {
        if (isApiError(error) && error.response?.status === 422) {
            formError.value = typeof error.response.data?.message === 'string'
                ? error.response.data.message
                : t('preferences.save_error');

            return;
        }

        formError.value = t('preferences.save_error');
    }
});

function districtName(district: DistrictSummary): string {
    return [district.bailiff_name, district.bailiff_surname].filter((part): part is string => part !== null && part !== '').join(' ');
}
</script>
