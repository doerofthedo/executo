import { defineStore } from 'pinia';
import type { CurrentUser, LoginInput } from '@/api/auth';
import { fetchCurrentUser, login, logout } from '@/api/auth';

interface AuthState {
    token: string | null;
    user: CurrentUser | null;
}

export const useAuthStore = defineStore('auth', {
    state: (): AuthState => ({
        token: null,
        user: null,
    }),
    actions: {
        async signIn(input: LoginInput): Promise<void> {
            const session = await login(input);

            this.token = session.token;
            this.user = session.user;
        },
        async loadCurrentUser(): Promise<void> {
            this.user = await fetchCurrentUser();
        },
        async signOut(): Promise<void> {
            await logout();
            this.clearSession();
        },
        clearSession(): void {
            this.token = null;
            this.user = null;
        },
    },
});
