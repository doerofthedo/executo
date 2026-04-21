import { z } from 'zod';
import { apiClient } from './client';

export const loginSchema = z.object({
    login: z.string().min(1),
    password: z.string().min(1),
});

export type LoginInput = z.infer<typeof loginSchema>;

export const registerSchema = z.object({
    name: z.string().min(1),
    surname: z.string().min(1),
    email: z.email(),
    password: z.string().min(8),
    password_confirmation: z.string().min(8),
    locale: z.enum(['lv', 'en']),
}).refine((data) => data.password === data.password_confirmation, {
    path: ['password_confirmation'],
    message: 'Passwords do not match.',
});

export type RegisterInput = z.infer<typeof registerSchema>;

export const forgotPasswordSchema = z.object({
    email: z.email(),
});

export type ForgotPasswordInput = z.infer<typeof forgotPasswordSchema>;

export const resetPasswordSchema = z.object({
    email: z.email(),
    token: z.string().min(1),
    password: z.string().min(8),
    password_confirmation: z.string().min(8),
}).refine((data) => data.password === data.password_confirmation, {
    path: ['password_confirmation'],
    message: 'Passwords do not match.',
});

export type ResetPasswordInput = z.infer<typeof resetPasswordSchema>;

export interface CurrentUser {
    ulid: string | null;
    email: string | null;
    name: string | null;
    surname?: string | null;
    disabled?: boolean;
    is_email_verified?: boolean;
    email_verified_at?: string | null;
}

export interface AuthSessionResponse {
    token: string;
    user: CurrentUser;
}

export async function login(input: LoginInput): Promise<AuthSessionResponse> {
    const response = await apiClient.post<AuthSessionResponse>('/auth/login', input);

    return response.data;
}

export async function register(input: RegisterInput): Promise<void> {
    await apiClient.post('/auth/register', input);
}

export async function requestEmailVerification(email: string): Promise<void> {
    await apiClient.post('/auth/email/verification-request', { email });
}

export async function logout(): Promise<void> {
    await apiClient.post('/auth/logout');
}

export async function fetchCurrentUser(): Promise<CurrentUser> {
    const response = await apiClient.get<CurrentUser>('/auth/me');

    return response.data;
}

export async function forgotPassword(input: ForgotPasswordInput): Promise<void> {
    await apiClient.post('/auth/password/forgot', input);
}

export async function resetPassword(input: ResetPasswordInput): Promise<void> {
    await apiClient.post('/auth/password/reset', input);
}

export async function verifyEmail(url: string): Promise<void> {
    await apiClient.get(url.startsWith('/api/') ? url.replace('/api/v1', '') : url.replace('/api/v1', ''), {
        baseURL: '/api/v1',
    });
}
