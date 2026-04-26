import { apiClient } from './client';
import type { Payment } from './payments';

export interface Debt {
    ulid: string;
    district_ulid: string | null;
    customer_ulid: string | null;
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

export async function listDebts(districtUlid: string, customerUlid: string): Promise<Debt[]> {
    const response = await apiClient.get<{ data: Debt[] }>(
        `/districts/${districtUlid}/customers/${customerUlid}/debts`,
    );

    return response.data.data;
}

export async function fetchDebtDetail(districtUlid: string, customerUlid: string, debtUlid: string): Promise<DebtDetail> {
    const response = await apiClient.get<{ data: DebtDetail }>(
        `/districts/${districtUlid}/customers/${customerUlid}/debts/${debtUlid}`,
    );

    return response.data.data;
}
