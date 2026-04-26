import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

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

            if (window.location.pathname !== '/login') {
                window.location.assign('/login');
            }
        }

        return Promise.reject(error);
    },
);

export function isApiError(error: unknown): error is import('axios').AxiosError<any, any> {
    return axios.isAxiosError(error);
}
