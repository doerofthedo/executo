import { z } from 'zod';
import { apiClient } from './client';

type Translate = (key: string, params?: Record<string, string | number>) => string;

export type DistrictUserRole = 'district.admin' | 'district.manager' | 'district.user';

export interface DistrictUser {
    ulid: string;
    email: string | null;
    name: string | null;
    surname: string | null;
    role: DistrictUserRole | null;
    permissions: string[];
    is_owner: boolean;
    disabled: boolean;
    is_email_verified: boolean;
}

export interface DistrictUserInviteInput {
    email: string;
    role: DistrictUserRole;
}

export interface DistrictUserRoleUpdateInput {
    role: DistrictUserRole;
}

export interface UserPreferences {
    default_district_ulid: string | null;
    locale: 'lv' | 'en';
    timezone: string;
    date_format: string;
    decimal_separator: string;
    thousand_separator: string;
    table_page_size: number;
}

export interface UserProfile {
    ulid: string;
    email: string | null;
    name: string | null;
    surname: string | null;
    disabled: boolean;
    is_email_verified: boolean;
    email_verified_at: string | null;
    preferences: {
        default_district_ulid: string | null;
        locale: string | null;
        timezone: string | null;
        date_format: string | null;
        decimal_separator: string | null;
        thousand_separator: string | null;
        table_page_size: number | null;
    };
}

export function createDistrictUserSchema(t: Translate) {
    return z.object({
        email: z.string().email(t('auth.validation.invalid_email')),
        role: z.enum(['district.admin', 'district.manager', 'district.user']),
    });
}

export function createUserPreferencesSchema(t: Translate) {
    return z.object({
        default_district_ulid: z.string().trim().length(26).nullable(),
        locale: z.enum(['lv', 'en']),
        timezone: z.enum(['Europe/Riga', 'UTC']),
        date_format: z.string().trim().min(1, t('preferences.validation.date_format_required')),
        decimal_separator: z.enum(['.', ',']),
        thousand_separator: z.enum([' ', '.', ',', "'"]),
        table_page_size: z.coerce.number().int().min(10).max(100),
    });
}

export type DistrictUserFormInput = z.infer<ReturnType<typeof createDistrictUserSchema>>;
export type UserPreferencesInput = z.infer<ReturnType<typeof createUserPreferencesSchema>>;

export async function getDistrictUsers(districtUlid: string): Promise<DistrictUser[]> {
    const response = await apiClient.get<{ data: DistrictUser[] } | DistrictUser[]>(`/districts/${districtUlid}/users`);

    return 'data' in response.data ? response.data.data : response.data;
}

export async function updateDistrictUserRole(
    districtUlid: string,
    userUlid: string,
    role: DistrictUserRole,
): Promise<DistrictUser> {
    const response = await apiClient.patch<{ data: DistrictUser } | DistrictUser>(`/districts/${districtUlid}/users/${userUlid}`, {
        role,
    } satisfies DistrictUserRoleUpdateInput);

    return 'data' in response.data ? response.data.data : response.data;
}

export async function removeDistrictUser(districtUlid: string, userUlid: string): Promise<void> {
    await apiClient.delete(`/districts/${districtUlid}/users/${userUlid}`);
}

export async function inviteDistrictUser(districtUlid: string, email: string, role: DistrictUserRole): Promise<DistrictUser> {
    const response = await apiClient.post<{ data: DistrictUser } | DistrictUser>(`/districts/${districtUlid}/users`, {
        email,
        role,
    } satisfies DistrictUserInviteInput);

    return 'data' in response.data ? response.data.data : response.data;
}

export async function fetchUserProfile(userUlid: string): Promise<UserProfile> {
    const response = await apiClient.get<{ data: UserProfile } | UserProfile>(`/users/${userUlid}`);

    return 'data' in response.data ? response.data.data : response.data;
}

export async function updateUserPreferences(userUlid: string, input: UserPreferencesInput): Promise<UserProfile> {
    const response = await apiClient.patch<{ data: UserProfile } | UserProfile>(`/users/${userUlid}`, input);

    return 'data' in response.data ? response.data.data : response.data;
}
