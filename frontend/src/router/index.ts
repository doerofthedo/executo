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
import UserManagementPage from '@/pages/UserManagementPage.vue';
import { fetchAccessibleDistricts } from '@/api/districts';
import { getDashboardStats } from '@/api/dashboard';
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
            meta: { requiresAuth: true, requiresManageUsers: true },
        },
        {
            path: '/districts/:district/users',
            name: 'user-management',
            component: UserManagementPage,
            meta: { requiresAuth: true, requiresManageUsers: true },
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
            path: '/verify-email',
            name: 'verify-email',
            component: RegisterPage,
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
        return { name: 'login' };
    }

    if (to.meta.guestOnly && authStore.isAuthenticated) {
        return { name: 'dashboard' };
    }

    const districtParam = typeof to.params.district === 'string' ? to.params.district : null;

    if (districtParam !== null) {
        try {
            const accessibleDistricts = await fetchAccessibleDistricts();
            const hasDistrictAccess = accessibleDistricts.some((district) => district.ulid === districtParam);

            if (!hasDistrictAccess) {
                return { name: 'dashboard' };
            }

            if (to.meta.requiresManageUsers === true) {
                const stats = await getDashboardStats();
                const canManageUsers = stats.data.some((card) => card.district.ulid === districtParam && card.can_manage_users);
                const isAppAdmin = authStore.user?.is_app_admin === true;

                if (!canManageUsers && !isAppAdmin) {
                    return { name: 'dashboard' };
                }
            }
        } catch {
            return { name: 'dashboard' };
        }
    }

    return true;
});
