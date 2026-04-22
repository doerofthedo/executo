import { defineStore } from 'pinia';

interface PreferencesState {
    locale: 'lv' | 'en';
    dateFormat: string;
    decimalSeparator: string;
    thousandSeparator: string;
}

export const usePreferencesStore = defineStore('preferences', {
    state: (): PreferencesState => ({
        locale: 'lv',
        dateFormat: 'DD.MM.YYYY.',
        decimalSeparator: ',',
        thousandSeparator: ' ',
    }),
});
