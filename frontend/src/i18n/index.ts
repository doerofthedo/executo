import { createI18n } from 'vue-i18n';
import en from './en.json';
import lv from './lv.json';

export const i18n = createI18n({
    legacy: false,
    locale: 'lv',
    fallbackLocale: 'en',
    messages: {
        en,
        lv,
    },
});
