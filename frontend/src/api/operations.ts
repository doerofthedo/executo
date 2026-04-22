import { z } from 'zod';
import { apiClient } from './client';

type Translate = (key: string, params?: Record<string, string | number>) => string;

export interface CustomerOption {
    ulid: string;
    name: string | null;
    case_number: string | null;
}

export interface DebtOption {
    ulid: string;
    amount: string;
    date: string;
    description: string | null;
}

export interface DistrictUserInput {
    email: string;
    role: 'district.admin' | 'district.manager' | 'district.user';
}

export interface CustomerCreateInput {
    case_number: string | null;
    type: 'physical' | 'legal';
    email: string | null;
    phone: string | null;
    first_name: string | null;
    last_name: string | null;
    personal_code: string | null;
    date_of_birth: string | null;
    company_name: string | null;
    registration_number: string | null;
    contact_person: string | null;
}

export interface DebtCreateInput {
    amount: string;
    date: string;
    description: string | null;
}

export interface PaymentCreateInput {
    amount: string;
    date: string;
    description: string | null;
}

export function createDistrictUserSchema(t: Translate) {
    return z.object({
        email: z.email(t('auth.validation.invalid_email')),
        role: z.enum(['district.admin', 'district.manager', 'district.user']),
    });
}

export function createCustomerSchema(t: Translate) {
    return z.object({
        case_number: z.string().trim().max(255).nullable(),
        type: z.enum(['physical', 'legal']),
        email: z.email(t('auth.validation.invalid_email')).nullable().or(z.literal('')),
        phone: z.string().trim().max(255).nullable(),
        first_name: z.string().trim().max(255).nullable(),
        last_name: z.string().trim().max(255).nullable(),
        personal_code: z.string().trim().max(255).nullable(),
        date_of_birth: z.string().trim().nullable(),
        company_name: z.string().trim().max(255).nullable(),
        registration_number: z.string().trim().max(255).nullable(),
        contact_person: z.string().trim().max(255).nullable(),
    }).superRefine((data, context) => {
        if (data.type === 'physical') {
            if (!data.first_name) {
                context.addIssue({ code: z.ZodIssueCode.custom, path: ['first_name'], message: t('auth.validation.field_required') });
            }

            if (!data.last_name) {
                context.addIssue({ code: z.ZodIssueCode.custom, path: ['last_name'], message: t('auth.validation.field_required') });
            }
        }

        if (data.type === 'legal' && !data.company_name) {
            context.addIssue({ code: z.ZodIssueCode.custom, path: ['company_name'], message: t('auth.validation.field_required') });
        }
    });
}

export function createDebtSchema(t: Translate) {
    return z.object({
        customer_ulid: z.string().trim().min(1, t('auth.validation.field_required')),
        amount: z.string().trim().min(1, t('auth.validation.field_required')),
        date: z.string().trim().min(1, t('auth.validation.field_required')),
        description: z.string().trim().nullable(),
    });
}

export function createPaymentSchema(t: Translate) {
    return z.object({
        customer_ulid: z.string().trim().min(1, t('auth.validation.field_required')),
        debt_ulid: z.string().trim().min(1, t('auth.validation.field_required')),
        amount: z.string().trim().min(1, t('auth.validation.field_required')),
        date: z.string().trim().min(1, t('auth.validation.field_required')),
        description: z.string().trim().nullable(),
    });
}

export async function assignDistrictUser(districtUlid: string, input: DistrictUserInput): Promise<void> {
    await apiClient.post(`/districts/${districtUlid}/users`, input);
}

export async function createCustomer(districtUlid: string, input: CustomerCreateInput): Promise<void> {
    await apiClient.post(`/districts/${districtUlid}/customers`, input);
}

export async function fetchDistrictCustomers(districtUlid: string): Promise<CustomerOption[]> {
    const response = await apiClient.get<{ data: CustomerOption[] } | CustomerOption[]>(`/districts/${districtUlid}/customers`, {
        params: {
            per_page: 100,
        },
    });

    return 'data' in response.data ? response.data.data : response.data;
}

export async function createDebt(districtUlid: string, customerUlid: string, input: DebtCreateInput): Promise<void> {
    await apiClient.post(`/districts/${districtUlid}/customers/${customerUlid}/debts`, input);
}

export async function fetchCustomerDebts(districtUlid: string, customerUlid: string): Promise<DebtOption[]> {
    const response = await apiClient.get<{ data: DebtOption[] } | DebtOption[]>(`/districts/${districtUlid}/customers/${customerUlid}/debts`);

    return 'data' in response.data ? response.data.data : response.data;
}

export async function createPayment(
    districtUlid: string,
    customerUlid: string,
    debtUlid: string,
    input: PaymentCreateInput,
): Promise<void> {
    await apiClient.post(`/districts/${districtUlid}/customers/${customerUlid}/debts/${debtUlid}/payments`, input);
}
