import { z } from 'zod';
import { apiClient } from './client';

export const loginSchema = z.object({
    login: z.string().min(1),
    password: z.string().min(1),
});

export type LoginInput = z.infer<typeof loginSchema>;

export interface CurrentUser {
    ulid: string | null;
    email: string | null;
    name: string | null;
}

export interface AuthSessionResponse {
    token: string;
    user: CurrentUser;
}

export async function login(input: LoginInput): Promise<AuthSessionResponse> {
    const response = await apiClient.post<AuthSessionResponse>('/auth/login', input);

    return response.data;
}

export async function logout(): Promise<void> {
    await apiClient.post('/auth/logout');
}

export async function fetchCurrentUser(): Promise<CurrentUser> {
    const response = await apiClient.get<CurrentUser>('/auth/me');

    return response.data;
}
