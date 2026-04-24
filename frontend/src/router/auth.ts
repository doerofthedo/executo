import { createRouter, createWebHistory } from 'vue-router';
import ForgotPasswordPage from '@/pages/ForgotPasswordPage.vue';
import LoginPage from '@/pages/LoginPage.vue';
import RegisterPage from '@/pages/RegisterPage.vue';
import ResetPasswordPage from '@/pages/ResetPasswordPage.vue';
import { useAuthStore } from '@/stores/auth';

function visitAppShell(path: string): false {
    window.location.assign(path);

    return false;
}

export const authRouter = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            redirect: { name: 'login' },
        },
        {
            path: '/login',
            name: 'login',
            component: LoginPage,
        },
        {
            path: '/register',
            name: 'register',
            component: RegisterPage,
        },
        {
            path: '/verify-email',
            name: 'verify-email',
            component: RegisterPage,
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: ForgotPasswordPage,
        },
        {
            path: '/reset-password',
            name: 'reset-password',
            component: ResetPasswordPage,
        },
    ],
});

authRouter.beforeEach(async () => {
    const authStore = useAuthStore();

    await authStore.bootstrap();

    if (authStore.isAuthenticated) {
        return visitAppShell('/dashboard');
    }

    return true;
});
