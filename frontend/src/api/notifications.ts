import { apiClient } from './client';

export interface AppNotification {
    id: string;
    type: string;
    data: Record<string, string>;
    read_at: string | null;
    created_at: string;
}

export interface NotificationsResponse {
    data: AppNotification[];
    unread_count: number;
}

export async function fetchNotifications(): Promise<NotificationsResponse> {
    const response = await apiClient.get<NotificationsResponse>('/notifications');
    return response.data;
}

export async function markNotificationRead(id: string): Promise<void> {
    await apiClient.post(`/notifications/${id}/read`);
}

export async function markAllNotificationsRead(): Promise<void> {
    await apiClient.post('/notifications/read-all');
}
