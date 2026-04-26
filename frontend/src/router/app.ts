import { createRouter, createWebHistory } from 'vue-router';
import type { Component } from 'vue';
import DebtorCreatePage from '@/pages/DebtorCreatePage.vue';
import DebtorDetailPage from '@/pages/DebtorDetailPage.vue';
import DebtorListPage from '@/pages/DebtorListPage.vue';
import DebtCreatePage from '@/pages/DebtCreatePage.vue';
import DebtDetailPage from '@/pages/DebtDetailPage.vue';
import DashboardPage from '@/pages/DashboardPage.vue';
import DistrictPage from '@/pages/DistrictPage.vue';
import DistrictUserCreatePage from '@/pages/DistrictUserCreatePage.vue';
import PaymentCreatePage from '@/pages/PaymentCreatePage.vue';
import PaymentListPage from '@/pages/PaymentListPage.vue';
import PreferencesPage from '@/pages/PreferencesPage.vue';
import ProfilePage from '@/pages/ProfilePage.vue';
import UserManagementPage from '@/pages/UserManagementPage.vue';
import { fetchAccessibleDistricts } from '@/api/districts';
import { getDashboardStats } from '@/api/dashboard';
import { useAuthStore } from '@/stores/auth';

const ShellBoundary: Component = () => null;

function visitAuthShell(path: string): false {
  window.location.assign(path);

  return false;
}

export const appRouter = createRouter({
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
    },
    {
      path: '/districts/:district',
      name: 'district',
      component: DistrictPage,
    },
    {
      path: '/districts/:district/debtors',
      name: 'debtors',
      component: DebtorListPage,
    },
    {
      path: '/districts/:district/debtors/create',
      name: 'debtor-create',
      component: DebtorCreatePage,
    },
    {
      path: '/districts/:district/debtors/:debtor',
      name: 'debtor',
      component: DebtorDetailPage,
    },
    {
      path: '/districts/:district/debtors/:debtor/debts/:debt',
      name: 'debt',
      component: DebtDetailPage,
    },
    {
      path: '/districts/:district/debtors/:debtor/debts/:debt/payments',
      name: 'payments',
      component: PaymentListPage,
    },
    {
      path: '/districts/:district/debts/create',
      name: 'debt-create',
      component: DebtCreatePage,
    },
    {
      path: '/districts/:district/payments/create',
      name: 'payment-create',
      component: PaymentCreatePage,
    },
    {
      path: '/districts/:district/users/create',
      name: 'district-user-create',
      component: DistrictUserCreatePage,
      meta: { requiresManageUsers: true },
    },
    {
      path: '/districts/:district/users',
      name: 'user-management',
      component: UserManagementPage,
      meta: { requiresManageUsers: true },
    },
    {
      path: '/profile',
      name: 'profile',
      component: ProfilePage,
    },
    {
      path: '/preferences',
      name: 'preferences',
      component: PreferencesPage,
    },
    {
      path: '/login',
      component: ShellBoundary,
      beforeEnter: () => visitAuthShell('/login'),
    },
    {
      path: '/register',
      component: ShellBoundary,
      beforeEnter: () => visitAuthShell('/register'),
    },
    {
      path: '/forgot-password',
      component: ShellBoundary,
      beforeEnter: () => visitAuthShell('/forgot-password'),
    },
    {
      path: '/reset-password',
      component: ShellBoundary,
      beforeEnter: () => visitAuthShell('/reset-password'),
    },
    {
      path: '/verify-email',
      component: ShellBoundary,
      beforeEnter: () => visitAuthShell('/verify-email'),
    },
  ],
});

appRouter.beforeEach(async (to) => {
  const authStore = useAuthStore();

  await authStore.bootstrap();

  if (!authStore.isAuthenticated) {
    return visitAuthShell('/login');
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
