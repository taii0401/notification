import apiClient from './client';

export async function getTemplates(
    projectUuid,
    {
        keyword = '',
        channel = '',
        status = '',
        page = 1,
        perPage = 20,
    } = {}
) {
    const response = await apiClient.get(
        `/projects/${projectUuid}/templates`,
        {
            params: {
                keyword: keyword || undefined,
                channel: channel || undefined,
                status: status || undefined,
                page,
                per_page: perPage,
            },
        }
    );

    return {
        data: response.data.data,
        meta: response.data.meta,
        options: response.data.options,
    };
}

export async function createTemplate(projectUuid, payload) {
    const response = await apiClient.post(
        `/projects/${projectUuid}/templates`,
        payload
    );

    return response.data.data;
}

export async function updateTemplate(
    projectUuid,
    templateId,
    payload
) {
    const response = await apiClient.put(
        `/projects/${projectUuid}/templates/${templateId}`,
        payload
    );

    return response.data.data;
}

export async function deleteTemplate(projectUuid, templateId) {
    await apiClient.delete(
        `/projects/${projectUuid}/templates/${templateId}`
    );
}
