import { apiClient } from './client';

export interface Debtor {
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

export interface DebtorListMeta {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

export interface DebtorListResponse {
  data: Debtor[];
  meta: DebtorListMeta;
}

export interface DebtorListParams {
  page?: number;
  per_page?: number;
  search?: string;
  type?: 'physical' | 'legal';
  include_trashed?: boolean;
}

export function debtorDisplayName(debtor: Debtor): string {
  return (
    debtor.company_name ||
    [debtor.first_name, debtor.last_name].filter(Boolean).join(' ') ||
    debtor.name ||
    debtor.ulid
  );
}

export async function listDebtors(districtUlid: string, params: DebtorListParams = {}): Promise<DebtorListResponse> {
  const response = await apiClient.get<DebtorListResponse>(`/districts/${districtUlid}/debtors`, { params });

  return response.data;
}

export async function fetchDebtor(districtUlid: string, debtorUlid: string): Promise<Debtor> {
  const response = await apiClient.get<{ data: Debtor }>(`/districts/${districtUlid}/debtors/${debtorUlid}`);

  return response.data.data;
}
