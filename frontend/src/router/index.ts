import { createRouter, createWebHistory } from 'vue-router';
import CustomerCreatePage from '@/pages/CustomerCreatePage.vue';
import DebtCreatePage from '@/pages/DebtCreatePage.vue';
import DashboardPage from '@/pages/DashboardPage.vue';
import DistrictUserCreatePage from '@/pages/DistrictUserCreatePage.vue';
import ForgotPasswordPage from '@/pages/ForgotPasswordPage.vue';
import LoginPage from '@/pages/LoginPage.vue';
import PaymentCreatePage from '@/pages/PaymentCreatePage.vue';
import PreferencesPage from '@/pages/PreferencesPage.vue';
import ProfilePage from '@/pages/ProfilePage.vue';
import RegisterPage from '@/pages/RegisterPage.vue';
import ResetPasswordPage from '@/pages/ResetPasswordPage.vue';
import { useAuthStore } from '@/stores/auth';

export const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            redirect: { name: 'dashboard' },
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: DashboardPage,
            meta: { requiresAuth: true },
        },
        {
            path: '/districts/:district',
            name: 'district',
            component: DashboardPage,
            meta: { requiresAuth: true },
        },
        {
            path: '/districts/:district/customers/create',
            name: 'customer-create',
            component: CustomerCreatePage,
            meta: { requiresAuth: true },
        },
        {
            path: '/districts/:district/debts/create',
            name: 'debt-create',
            component: DebtCreatePage,
            meta: { requiresAuth: true },
        },
        {
            path: '/districts/:district/payments/create',
            name: 'payment-create',
            component: PaymentCreatePage,
            meta: { requiresAuth: true },
        },
        {
            path: '/districts/:district/users/create',
            name: 'district-user-create',
            component: DistrictUserCreatePage,
            meta: { requiresAuth: true },
        },
        {
            path: '/profile',
            name: 'profile',
            component: ProfilePage,
            meta: { requiresAuth: true },
        },
        {
            path: '/preferences',
            name: 'preferences',
            component: PreferencesPage,
            meta: { requiresAuth: true },
        },
        {
            path: '/login',
            name: 'login',
            component: LoginPage,
            meta: { guestOnly: true },
        },
        {
            path: '/register',
            name: 'register',
            component: RegisterPage,
            meta: { guestOnly: true },
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: ForgotPasswordPage,
            meta: { guestOnly: true },
        },
        {
            path: '/reset-password',
            name: 'reset-password',
            component: ResetPasswordPage,
            meta: { guestOnly: true },
        },
    ],
});

router.beforeEach(async (to) => {
    const authStore = useAuthStore();

    await authStore.bootstrap();

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return {
            name: 'login',
            query: {
                redirect: to.fullPath,
            },
        };
    }

    if (to.meta.guestOnly && authStore.isAuthenticated) {
        return { name: 'dashboard' };
    }

    return true;
});
