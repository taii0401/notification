import apiClient from './client';

export async function getNotifications(
    projectUuid,
    { channel = '', status = '', page = 1 } = {}
) {
    const response = await apiClient.get(
        `/projects/${projectUuid}/notifications`,
        {
            params: {
                channel: channel || undefined,
                status: status || undefined,
                page,
            },
        }
    );

    return {
        data: response.data.data,
        meta: response.data.meta,
        options: response.data.options,
    };
}