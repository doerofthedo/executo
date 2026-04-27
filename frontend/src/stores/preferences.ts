import { defineStore } from 'pinia';

interface PreferencesState {
    locale: 'lv' | 'en';
    timezone: string;
    dateFormat: string;
    decimalSeparator: string;
    thousandSeparator: string;
    tablePageSize: number;
}

export const usePreferencesStore = defineStore('preferences', {
    state: (): PreferencesState => ({
        locale: 'lv',
        timezone: 'Europe/Riga',
        dateFormat: 'DD.MM.YYYY.',
        decimalSeparator: ',',
        thousandSeparator: ' ',
        tablePageSize: 25,
    }),
});
