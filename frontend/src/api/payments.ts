import { apiClient } from './client';

export interface Payment {
  ulid: string;
  debtor_ulid: string | null;
  debt_ulid: string | null;
  amount: string;
  date: string | null;
  description: string | null;
}

export interface PaymentInput {
  amount: string;
  date: string;
  description?: string | null;
}

export async function listPayments(districtUlid: string, debtorUlid: string, debtUlid: string): Promise<Payment[]> {
  const response = await apiClient.get<{ data: Payment[] }>(
    `/districts/${districtUlid}/debtors/${debtorUlid}/debts/${debtUlid}/payments`,
  );

  return response.data.data;
}

export async function createPayment(
  districtUlid: string,
  debtorUlid: string,
  debtUlid: string,
  input: PaymentInput,
): Promise<Payment> {
  const response = await apiClient.post<{ data: Payment }>(
    `/districts/${districtUlid}/debtors/${debtorUlid}/debts/${debtUlid}/payments`,
    input,
  );

  return response.data.data;
}

export async function updatePayment(
  districtUlid: string,
  debtorUlid: string,
  debtUlid: string,
  paymentUlid: string,
  input: PaymentInput,
): Promise<Payment> {
  const response = await apiClient.patch<{ data: Payment }>(
    `/districts/${districtUlid}/debtors/${debtorUlid}/debts/${debtUlid}/payments/${paymentUlid}`,
    input,
  );

  return response.data.data;
}

export async function fetchPayment(
  districtUlid: string,
  debtorUlid: string,
  debtUlid: string,
  paymentUlid: string,
): Promise<Payment> {
  const response = await apiClient.get<{ data: Payment }>(
    `/districts/${districtUlid}/debtors/${debtorUlid}/debts/${debtUlid}/payments/${paymentUlid}`,
  );

  return response.data.data;
}

export async function deletePayment(
  districtUlid: string,
  debtorUlid: string,
  debtUlid: string,
  paymentUlid: string,
): Promise<void> {
  await apiClient.delete(`/districts/${districtUlid}/debtors/${debtorUlid}/debts/${debtUlid}/payments/${paymentUlid}`);
}
