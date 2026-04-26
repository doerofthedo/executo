import { apiClient } from './client';
import type { Payment } from './payments';

export interface Debt {
  ulid: string;
  district_ulid: string | null;
  debtor_ulid: string | null;
  amount: string;
  date: string | null;
  description: string | null;
}

export interface InterestColumn {
  key: string;
  label_key: string;
  align: 'left' | 'right' | 'center';
}

export type InterestRow = Record<string, string | number | null>;

export interface DebtDetail {
  debt: Debt;
  payments: Payment[];
  interest: {
    columns: InterestColumn[];
    rows: InterestRow[];
    total_row: InterestRow;
  };
}

export async function listDebts(districtUlid: string, debtorUlid: string): Promise<Debt[]> {
  const response = await apiClient.get<{ data: Debt[] }>(
    `/districts/${districtUlid}/debtors/${debtorUlid}/debts`,
  );

  return response.data.data;
}

export async function fetchDebtDetail(districtUlid: string, debtorUlid: string, debtUlid: string): Promise<DebtDetail> {
  const response = await apiClient.get<{ data: DebtDetail }>(
    `/districts/${districtUlid}/debtors/${debtorUlid}/debts/${debtUlid}`,
  );

  return response.data.data;
}
