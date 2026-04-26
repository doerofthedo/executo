import { apiClient } from './client';

export interface Customer {
    ulid: string;
    district_ulid: string | null;
    name: string | null;
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
    is_deleted: boolean;
    deleted_at: string | null;
}

export interface CustomerListMeta {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

export interface CustomerListResponse {
    data: Customer[];
    meta: CustomerListMeta;
}

export interface CustomerListParams {
    page?: number;
    per_page?: number;
    search?: string;
    type?: 'physical' | 'legal';
    include_trashed?: boolean;
}

export function customerDisplayName(customer: Customer): string {
    return (
        customer.company_name ||
        [customer.first_name, customer.last_name].filter(Boolean).join(' ') ||
        customer.name ||
        customer.ulid
    );
}

export async function listCustomers(districtUlid: string, params: CustomerListParams = {}): Promise<CustomerListResponse> {
    const response = await apiClient.get<CustomerListResponse>(`/districts/${districtUlid}/customers`, { params });

    return response.data;
}

export async function fetchCustomer(districtUlid: string, customerUlid: string): Promise<Customer> {
    const response = await apiClient.get<{ data: Customer }>(`/districts/${districtUlid}/customers/${customerUlid}`);

    return response.data.data;
}
