import { z } from 'zod';
import { apiClient } from './client';

type Translate = (key: string) => string;

export interface UserPreferences {
    default_district_ulid: string | null;
    locale: 'lv' | 'en';
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
        date_format: string | null;
        decimal_separator: string | null;
        thousand_separator: string | null;
        table_page_size: number | null;
    };
}

export function createUserPreferencesSchema(t: Translate) {
    return z.object({
        default_district_ulid: z.string().trim().length(26).nullable(),
        locale: z.enum(['lv', 'en']),
        date_format: z.string().trim().min(1, t('preferences.validation.date_format_required')),
        decimal_separator: z.enum(['.', ',']),
        thousand_separator: z.enum([' ', '.', ',', "'"]),
        table_page_size: z.coerce.number().int().min(10).max(100),
    });
}

export type UserPreferencesInput = z.infer<ReturnType<typeof createUserPreferencesSchema>>;

export async function fetchUserProfile(userUlid: string): Promise<UserProfile> {
    const response = await apiClient.get<{ data: UserProfile } | UserProfile>(`/users/${userUlid}`);

    return 'data' in response.data ? response.data.data : response.data;
}

export async function updateUserPreferences(userUlid: string, input: UserPreferencesInput): Promise<UserProfile> {
    const response = await apiClient.patch<{ data: UserProfile } | UserProfile>(`/users/${userUlid}`, input);

    return 'data' in response.data ? response.data.data : response.data;
}
