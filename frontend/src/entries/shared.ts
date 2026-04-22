import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from '@/App.vue';
import { i18n } from '@/i18n';
import { router } from '@/router';
import '@fontsource/ibm-plex-sans/400.css';
import '@fontsource/ibm-plex-sans/500.css';
import '@fontsource/ibm-plex-sans/700.css';
import '@/styles/shared.css';
import 'remixicon/fonts/remixicon.css';

let isMounted = false;

export function mountExecuto(): void {
    if (isMounted) {
        return;
    }

    createApp(App)
        .use(createPinia())
        .use(i18n)
        .use(router)
        .mount('#app');

    isMounted = true;
}
