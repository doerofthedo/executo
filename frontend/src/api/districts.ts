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

export async function fetchAccessibleDistricts(): Promise<DistrictSummary[]> {
    const response = await apiClient.get<{ data: DistrictSummary[] } | DistrictSummary[]>('/districts');

    return 'data' in response.data ? response.data.data : response.data;
}
