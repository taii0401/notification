import apiClient from './client';

export async function getApiKeys(
    projectUuid,
    { keyword = '', page = 1 } = {}
) {
    const response = await apiClient.get(
        `/projects/${projectUuid}/api-keys`,
        {
            params: {
                keyword: keyword || undefined,
                page,
            },
        }
    );

    return {
        data: response.data.data,
        meta: response.data.meta,
    };
}

export async function createApiKey(projectUuid, payload) {
    const response = await apiClient.post(
        `/projects/${projectUuid}/api-keys`,
        payload
    );

    return response.data.data;
}

export async function regenerateApiKey(projectUuid, apiKeyId) {
    const response = await apiClient.post(
        `/projects/${projectUuid}/api-keys/${apiKeyId}/regenerate`
    );

    return response.data.data;
}

export async function deleteApiKey(projectUuid, apiKeyId) {
    await apiClient.delete(
        `/projects/${projectUuid}/api-keys/${apiKeyId}`
    );
}
