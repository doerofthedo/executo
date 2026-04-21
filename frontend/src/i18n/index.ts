import { createI18n } from 'vue-i18n';
import en from './en.json';
import lv from './lv.json';

const STORAGE_KEY = 'executo_locale';

function resolveInitialLocale(): 'lv' | 'en' {
    if (typeof window === 'undefined') {
        return 'lv';
    }

    const storedLocale = window.localStorage.getItem(STORAGE_KEY);

    return storedLocale === 'en' ? 'en' : 'lv';
}

export const i18n = createI18n({
    legacy: false,
    locale: resolveInitialLocale(),
    fallbackLocale: 'en',
    messages: {
        en,
        lv,
    },
});

export function setPreferredLocale(locale: 'lv' | 'en'): void {
    i18n.global.locale.value = locale;

    if (typeof window !== 'undefined') {
        window.localStorage.setItem(STORAGE_KEY, locale);
    }
}
