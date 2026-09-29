import apiClient from './client';

export async function getDashboardStats(projectUuid) {
    const response = await apiClient.get(`/projects/${projectUuid}/dashboard`);

    return response.data.data;
}

export async function getDeliveryChart(projectUuid, month) {
    const response = await apiClient.get(
        `/projects/${projectUuid}/dashboard/delivery-chart`,
        { params: { month } },
    );

    return response.data.data;
}