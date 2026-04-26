import { defineStore } from 'pinia';
import type { CurrentUser, LoginInput } from '@/api/auth';
import { fetchCurrentUser, login, logout } from '@/api/auth';
import { usePreferencesStore } from '@/stores/preferences';

const TOKEN_KEY = 'executo_auth_token';

interface AuthState {
    token: string | null;
    user: CurrentUser | null;
    bootstrapped: boolean;
}

export const useAuthStore = defineStore('auth', {
    state: (): AuthState => ({
        token: typeof window === 'undefined' ? null : window.localStorage.getItem(TOKEN_KEY),
        user: null,
        bootstrapped: false,
    }),
    getters: {
        isAuthenticated: (state): boolean => state.token !== null && state.user !== null,
        isAppAdmin: (state): boolean => state.user?.is_app_admin === true,
    },
    actions: {
        async signIn(input: LoginInput): Promise<void> {
            const session = await login(input);

            this.setToken(session.token);
            this.user = session.user;
            this.syncPreferencesFromUser();
            this.bootstrapped = true;
        },
        async loadCurrentUser(): Promise<void> {
            this.user = await fetchCurrentUser();
            this.syncPreferencesFromUser();
            this.bootstrapped = true;
        },
        async signOut(): Promise<void> {
            await logout();
            this.clearSession();
        },
        async bootstrap(): Promise<void> {
            if (this.bootstrapped) {
                return;
            }

            if (this.token === null) {
                this.bootstrapped = true;

                return;
            }

            try {
                await this.loadCurrentUser();
            } catch {
                this.clearSession();
            } finally {
                this.bootstrapped = true;
            }
        },
        setToken(token: string | null): void {
            this.token = token;

            if (typeof window === 'undefined') {
                return;
            }

            if (token === null) {
                window.localStorage.removeItem(TOKEN_KEY);

                return;
            }

            window.localStorage.setItem(TOKEN_KEY, token);
        },
        clearSession(): void {
            this.setToken(null);
            this.user = null;
            this.bootstrapped = true;
        },
        syncPreferencesFromUser(): void {
            const preferencesStore = usePreferencesStore();

            preferencesStore.timezone = this.user?.timezone ?? 'Europe/Riga';
        },
    },
});
