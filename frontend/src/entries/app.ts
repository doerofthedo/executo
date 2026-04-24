import '@/styles/app.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from '@/App.vue';
import { i18n } from '@/i18n';
import { appRouter } from '@/router/app';

createApp(App)
    .use(createPinia())
    .use(i18n)
    .use(appRouter)
    .mount('#app');
