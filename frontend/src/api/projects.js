import apiClient from './client';

export async function getProject(uuid) {
    const response = await apiClient.get(`/projects/${uuid}`);

    return response.data.data;
}