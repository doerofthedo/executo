import { apiClient } from './client';

export interface DashboardDistrict {
  ulid: string;
  number: number;
  bailiff_name: string | null;
  bailiff_surname: string | null;
  court: string | null;
  address: string | null;
  disabled: boolean;
  owner_ulid: string | null;
}

export interface DashboardDistrictCard {
  district: DashboardDistrict;
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

export interface DashboardStats {
  data: DashboardDistrictCard[];
  districts_count: number;
  debtors_count: number;
  debts_count: number;
  payments_count: number;
}

export async function getDashboardStats(): Promise<DashboardStats> {
  const response = await apiClient.get<DashboardStats>('/districts/stats');

  return response.data;
}
