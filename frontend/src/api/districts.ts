import { apiClient } from './client';

export interface DistrictSummary {
  ulid: string;
  number: number;
  bailiff_name: string | null;
  bailiff_surname: string | null;
  court: string | null;
  address: string | null;
  disabled: boolean;
  owner_ulid: string | null;
}

export interface DistrictStats {
  district: DistrictSummary;
  users_count: number | null;
  can_view_users_count: boolean;
  can_manage_users: boolean;
  can_create_debtor: boolean;
  can_create_debt: boolean;
  can_create_payment: boolean;
  debtors_count: number;
  debts_count: number;
  payments_count: number;
}

export async function fetchAccessibleDistricts(): Promise<DistrictSummary[]> {
  const response = await apiClient.get<{ data: DistrictSummary[] } | DistrictSummary[]>('/districts');

  return 'data' in response.data ? response.data.data : response.data;
}

export interface DistrictRecentPayment {
  ulid: string;
  debtor_ulid: string | null;
  debt_ulid: string | null;
  debtor_name: string | null;
  case_number: string | null;
  amount: string;
  date: string | null;
  description: string | null;
}

export async function fetchDistrictStats(districtUlid: string): Promise<DistrictStats> {
  const response = await apiClient.get<DistrictStats>(`/districts/${districtUlid}/stats`);

  return response.data;
}

export async function fetchDistrictRecentPayments(districtUlid: string, limit = 5): Promise<DistrictRecentPayment[]> {
  const response = await apiClient.get<{ data: DistrictRecentPayment[] }>(
    `/districts/${districtUlid}/payments`,
    { params: { per_page: limit } },
  );

  return response.data.data;
}
