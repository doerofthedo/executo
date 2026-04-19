import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { router } from '@/router';

export const apiClient = axios.create({
    baseURL: '/api/v1',
    withCredentials: true,
});

apiClient.interceptors.request.use((config) => {
    const authStore = useAuthStore();

    if (authStore.token !== null) {
        config.headers.Authorization = `Bearer ${authStore.token}`;
    }

    return config;
});

apiClient.interceptors.response.use(
    (response) => response,
    async (error: unknown) => {
        if (axios.isAxiosError(error) && error.response?.status === 401) {
            const authStore = useAuthStore();

            authStore.clearSession();
            await router.push({ name: 'login' });
        }

        return Promise.reject(error);
    },
);
