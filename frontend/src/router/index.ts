import { createRouter, createWebHistory } from 'vue-router';
import DashboardPage from '@/pages/DashboardPage.vue';
import LoginPage from '@/pages/LoginPage.vue';

export const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            redirect: { name: 'login' },
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: DashboardPage,
        },
        {
            path: '/districts/:district',
            name: 'district',
            component: DashboardPage,
        },
        {
            path: '/login',
            name: 'login',
            component: LoginPage,
        },
    ],
});
