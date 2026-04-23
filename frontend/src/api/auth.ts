import { z } from 'zod';
import { apiClient } from './client';

type Translate = (key: string) => string;

export function createLoginSchema(t: Translate) {
    return z.object({
        login: z.string().trim().min(1, t('auth.validation.field_required')),
        password: z.string().min(1, t('auth.validation.field_required')),
    });
}

export type LoginInput = z.infer<ReturnType<typeof createLoginSchema>>;

export function createRegisterSchema(t: Translate) {
    return z.object({
        name: z.string().trim().min(1, t('auth.validation.field_required')),
        surname: z.string().trim().min(1, t('auth.validation.field_required')),
        email: z.string().email(t('auth.validation.invalid_email')),
        password: z.string().min(8, t('auth.validation.password_too_short')),
        password_confirmation: z.string().min(1, t('auth.validation.field_required')),
        locale: z.enum(['lv', 'en']),
    }).refine((data) => data.password === data.password_confirmation, {
        path: ['password_confirmation'],
        message: t('auth.validation.passwords_no_match'),
    });
}

export type RegisterInput = z.infer<ReturnType<typeof createRegisterSchema>>;

export function createForgotPasswordSchema(t: Translate) {
    return z.object({
        email: z.string().email(t('auth.validation.invalid_email')),
    });
}

export type ForgotPasswordInput = z.infer<ReturnType<typeof createForgotPasswordSchema>>;

export function createResetPasswordSchema(t: Translate) {
    return z.object({
        token: z.string().min(1, t('auth.validation.link_invalid')),
        password: z.string().min(8, t('auth.validation.password_too_short')),
        password_confirmation: z.string().min(1, t('auth.validation.field_required')),
    }).refine((data) => data.password === data.password_confirmation, {
        path: ['password_confirmation'],
        message: t('auth.validation.passwords_no_match'),
    });
}

export type ResetPasswordInput = z.infer<ReturnType<typeof createResetPasswordSchema>>;

export interface CurrentUser {
    ulid: string | null;
    email: string | null;
    name: string | null;
    surname?: string | null;
    disabled?: boolean;
    is_email_verified?: boolean;
    email_verified_at?: string | null;
    default_district_ulid?: string | null;
    timezone?: string | null;
    is_app_admin?: boolean;
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
    const response = await apiClient.get<CurrentUser | { data: CurrentUser }>('/auth/me');

    return 'data' in response.data ? response.data.data : response.data;
}

export async function forgotPassword(input: ForgotPasswordInput): Promise<void> {
    await apiClient.post('/auth/password/forgot', input);
}

export async function resetPassword(input: ResetPasswordInput): Promise<void> {
    await apiClient.post('/auth/password/reset', input);
}

export async function verifyEmail(url: string): Promise<void> {
    await apiClient.get(url, {
        baseURL: undefined,
    });
}

export async function verifyEmailToken(token: string): Promise<void> {
    await apiClient.post('/auth/email/verify-token', { token });
}
