import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';

import DateTime from '../components/ui/DateTime';
import CustomSelect from '../components/ui/CustomSelect';
import Modal from '../components/ui/Modal';
import Pagination from '../components/ui/Pagination';
import {
    getNotifications,
    sendNotification,
} from '../api/notifications';
import { getTemplates } from '../api/templates';

const initialPagination = {
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
    from: null,
    to: null,
};

const allChannelsOption = {
    value: '',
    label: '全部通知管道',
};

const allStatusesOption = {
    value: '',
    label: '全部狀態',
};

const emptySendForm = {
    apiKey: '',
    channel: 'email',
    recipient: '',
    templateId: '',
    scheduledAt: '',
    data: '{\n  "customer_name": "王小明",\n  "customer_email": "customer@example.com",\n  "order_no": "ORD-001",\n  "amount": 1280,\n  "currency": "TWD"\n}',
};

export default function NotificationsPage() {
    const { uuid } = useParams();

    const [notifications, setNotifications] = useState([]);
    const [channelOptions, setChannelOptions] = useState([
        allChannelsOption,
    ]);
    const [statusOptions, setStatusOptions] = useState([
        allStatusesOption,
    ]);
    const [pagination, setPagination] = useState(initialPagination);
    const [loading, setLoading] = useState(true);
    const [reloadToken, setReloadToken] = useState(0);

    const [channelInput, setChannelInput] = useState('');
    const [statusInput, setStatusInput] = useState('');
    const [channel, setChannel] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);

    const [sendModalOpen, setSendModalOpen] = useState(false);
    const [sendForm, setSendForm] = useState(emptySendForm);
    const [sendErrors, setSendErrors] = useState({});
    const [sendRequestError, setSendRequestError] = useState('');
    const [sending, setSending] = useState(false);
    const [idempotencyKey, setIdempotencyKey] = useState('');
    const [sendTemplateOptions, setSendTemplateOptions] = useState([]);
    const [loadingSendTemplates, setLoadingSendTemplates] = useState(false);

    useEffect(() => {
        let ignore = false;

        async function loadNotifications() {
            setLoading(true);

            try {
                const result = await getNotifications(uuid, {
                    channel,
                    status,
                    page,
                });

                if (!ignore) {
                    setNotifications(result.data);
                    setPagination(result.meta);
                    setChannelOptions([
                        allChannelsOption,
                        ...(result.options?.channels ?? []),
                    ]);
                    setStatusOptions([
                        allStatusesOption,
                        ...(result.options?.status ?? []),
                    ]);
                }
            } catch (error) {
                if (!ignore) {
                    console.error('Failed to load notifications:', {
                        status: error.response?.status,
                        data: error.response?.data,
                    });
                    setNotifications([]);
                    setPagination(initialPagination);
                }
            } finally {
                if (!ignore) {
                    setLoading(false);
                }
            }
        }

        loadNotifications();

        return () => {
            ignore = true;
        };
    }, [uuid, channel, status, page, reloadToken]);

    useEffect(() => {
        if (!sendModalOpen || !sendForm.channel) {
            setSendTemplateOptions([]);
            return undefined;
        }

        let ignore = false;

        async function loadSendTemplates() {
            setLoadingSendTemplates(true);

            try {
                const result = await getTemplates(uuid, {
                    channel: sendForm.channel,
                    status: 'active',
                    perPage: 100,
                });

                if (!ignore) {
                    setSendTemplateOptions(
                        result.data.map((template) => ({
                            value: String(template.id),
                            label: template.name,
                        }))
                    );
                }
            } catch (error) {
                if (!ignore) {
                    console.error('Failed to load templates:', {
                        status: error.response?.status,
                        data: error.response?.data,
                    });
                    setSendTemplateOptions([]);
                    setSendRequestError('無法載入 Template，請稍後再試。');
                }
            } finally {
                if (!ignore) {
                    setLoadingSendTemplates(false);
                }
            }
        }

        loadSendTemplates();

        return () => {
            ignore = true;
        };
    }, [uuid, sendModalOpen, sendForm.channel]);

    function handleSearch(event) {
        event.preventDefault();
        setChannel(channelInput);
        setStatus(statusInput);
        setPage(1);
    }

    function handleClearSearch() {
        setChannelInput('');
        setStatusInput('');
        setChannel('');
        setStatus('');
        setPage(1);
    }

    function openSendModal() {
        const defaultChannel = channelOptions.find(
            (option) => option.value
        )?.value ?? 'email';

        setSendForm({
            ...emptySendForm,
            channel: defaultChannel,
        });
        setSendErrors({});
        setSendRequestError('');
        setIdempotencyKey(crypto.randomUUID());
        setSendModalOpen(true);
    }

    function closeSendModal() {
        if (sending) {
            return;
        }

        setSendModalOpen(false);
        setSendForm(emptySendForm);
        setSendErrors({});
        setSendRequestError('');
        setIdempotencyKey('');
    }

    function handleSendFormChange(event) {
        const { name, value } = event.target;

        setSendForm((current) => ({
            ...current,
            [name]: value,
        }));
        setSendErrors((current) => ({
            ...current,
            [name]: undefined,
        }));
        setSendRequestError('');
    }

    function handleSendChannelChange(value) {
        setSendForm((current) => ({
            ...current,
            channel: value,
            recipient: '',
            templateId: '',
        }));
        setSendErrors((current) => ({
            ...current,
            channel: undefined,
            recipient: undefined,
            templateId: undefined,
        }));
        setSendRequestError('');
    }

    function handleSendTemplateChange(value) {
        setSendForm((current) => ({
            ...current,
            templateId: value,
        }));
        setSendErrors((current) => ({
            ...current,
            templateId: undefined,
        }));
        setSendRequestError('');
    }

    async function handleSendNotification(event) {
        event.preventDefault();

        const errors = {};
        let parsedData = null;

        if (!sendForm.apiKey.trim()) {
            errors.apiKey = ['請輸入完整 API Key。'];
        }

        if (!sendForm.channel) {
            errors.channel = ['請選擇通知管道。'];
        }

        if (sendForm.channel === 'email' && !sendForm.recipient.trim()) {
            errors.recipient = ['請輸入收件者 Email。'];
        }

        if (!sendForm.templateId) {
            errors.templateId = ['請選擇事件類型。'];
        }

        if (sendForm.data.trim()) {
            try {
                parsedData = JSON.parse(sendForm.data);

                if (
                    parsedData === null
                    || typeof parsedData !== 'object'
                ) {
                    errors.data = ['Data 必須是 JSON object 或 array。'];
                }
            } catch {
                errors.data = ['Data 不是有效的 JSON。'];
            }
        }

        if (Object.keys(errors).length > 0) {
            setSendErrors(errors);
            return;
        }

        const payload = {
            channel: sendForm.channel,
            template_id: Number(sendForm.templateId),
            data: parsedData,
            scheduled_at: sendForm.scheduledAt || null,
        };

        if (sendForm.channel === 'email') {
            payload.recipient = sendForm.recipient.trim();
        }

        try {
            setSending(true);
            setSendErrors({});
            setSendRequestError('');

            await sendNotification(
                sendForm.apiKey.trim(),
                payload,
                idempotencyKey
            );

            setSendModalOpen(false);
            setSendForm(emptySendForm);
            setIdempotencyKey('');
            setPage(1);
            setReloadToken((value) => value + 1);
        } catch (error) {
            console.error('Failed to send notification:', {
                status: error.response?.status,
                data: error.response?.data,
            });

            if (error.response?.status === 422) {
                const validationErrors = error.response.data.errors ?? {};

                setSendErrors({
                    channel: validationErrors.channel,
                    recipient: validationErrors.recipient,
                    templateId: validationErrors.template_id,
                    data: validationErrors.data,
                    scheduledAt: validationErrors.scheduled_at,
                });
            } else {
                setSendRequestError(
                    error.response?.data?.message
                    ?? '發送通知失敗，請確認 API Key 與通知資料。'
                );
            }
        } finally {
            setSending(false);
        }
    }

    return (
        <div className="list-page">
            <div className="list-toolbar">
                <form className="list-search" onSubmit={handleSearch}>
                    <CustomSelect
                        value={channelInput}
                        ariaLabel="通知管道"
                        options={channelOptions}
                        disabled={loading}
                        onChange={setChannelInput}
                    />

                    <CustomSelect
                        value={statusInput}
                        ariaLabel="通知狀態"
                        options={statusOptions}
                        disabled={loading}
                        onChange={setStatusInput}
                    />

                    <button
                        type="submit"
                        className="secondary-button"
                        disabled={loading}
                    >
                        搜尋
                    </button>

                    {(channel || status) && (
                        <button
                            type="button"
                            className="text-button"
                            onClick={handleClearSearch}
                        >
                            清除
                        </button>
                    )}
                </form>

                <button
                    type="button"
                    className="primary-button"
                    onClick={openSendModal}
                >
                    發送通知
                </button>
            </div>

            <div className="dashboard-card list-card">
                {loading ? (
                    <div className="list-state">載入中...</div>
                ) : (
                    <table className="notification-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>模板名稱</th>
                                <th>通知管道</th>
                                <th>通知狀態</th>
                                <th>排定派送時間</th>
                                <th>開始處理時間</th>
                                <th>成功派送時間</th>
                                <th>最終失敗時間</th>
                                <th>操作</th>
                            </tr>
                        </thead>

                        <tbody>
                            {notifications.length === 0 ? (
                                <tr>
                                    <td colSpan="9" className="empty-state">
                                        查無通知資料
                                    </td>
                                </tr>
                            ) : (
                                notifications.map((notification, index) => (
                                    <tr key={notification.uuid}>
                                        <td>
                                            {(pagination.current_page - 1) *
                                                pagination.per_page +
                                                index +
                                                1}
                                        </td>
                                        <td>{notification.template_name}</td>
                                        <td>{notification.channel_display}</td>
                                        <td>
                                            <span
                                                className={`badge badge-${notification.status}`}
                                            >
                                                {notification.status_display}
                                            </span>
                                        </td>
                                        <td>
                                            <DateTime
                                                value={notification.scheduled_at_display}
                                                multiline
                                            />
                                        </td>
                                        <td>
                                            <DateTime
                                                value={notification.processed_at_display}
                                                multiline
                                            />
                                        </td>
                                        <td>
                                            <DateTime
                                                value={notification.sent_at_display}
                                                multiline
                                            />
                                        </td>
                                        <td>
                                            <DateTime
                                                value={notification.failed_at_display}
                                                multiline
                                            />
                                        </td>
                                        <td>
                                            <div className="table-actions">
                                                <Link
                                                    className="table-action-button table-action-link regenerate-button"
                                                    title="檢視通知明細"
                                                    to={`/projects/${uuid}/notifications/${notification.uuid}`}
                                                >
                                                    檢視
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                )}

                {!loading && (
                    <Pagination
                        meta={pagination}
                        onPageChange={setPage}
                    />
                )}
            </div>

            <Modal
                open={sendModalOpen}
                title="發送通知"
                closeDisabled={sending}
                onClose={closeSendModal}
                actions={(
                    <>
                        <button
                            type="button"
                            className="secondary-button"
                            disabled={sending}
                            onClick={closeSendModal}
                        >
                            取消
                        </button>

                        <button
                            type="submit"
                            form="send-notification-form"
                            className="primary-button"
                            disabled={sending}
                        >
                            {sending ? '發送中...' : '發送'}
                        </button>
                    </>
                )}
            >
                <form
                    id="send-notification-form"
                    onSubmit={handleSendNotification}
                >
                    <SendField
                        label="完整 API Key"
                        name="apiKey"
                        type="password"
                        value={sendForm.apiKey}
                        error={sendErrors.apiKey?.[0]}
                        autoComplete="off"
                        autoFocus
                        required
                        onChange={handleSendFormChange}
                    />

                    <div className="form-grid">
                        <label className="form-field">
                            <span>通知管道</span>
                            <CustomSelect
                                value={sendForm.channel}
                                ariaLabel="發送通知的管道"
                                options={channelOptions.filter(
                                    (option) => option.value
                                )}
                                disabled={sending}
                                onChange={handleSendChannelChange}
                            />
                            {sendErrors.channel && (
                                <small className="field-error">
                                    {sendErrors.channel[0]}
                                </small>
                            )}
                        </label>

                        <label className="form-field">
                            <span>事件類型</span>
                            <CustomSelect
                                value={sendForm.templateId}
                                ariaLabel="事件類型"
                                options={[
                                    {
                                        value: '',
                                        label: loadingSendTemplates
                                            ? '載入中...'
                                            : sendTemplateOptions.length > 0
                                                ? '請選擇事件類型'
                                                : '此管道沒有啟用中的 Template',
                                    },
                                    ...sendTemplateOptions,
                                ]}
                                disabled={sending || loadingSendTemplates}
                                onChange={handleSendTemplateChange}
                            />
                            {sendErrors.templateId && (
                                <small className="field-error">
                                    {sendErrors.templateId[0]}
                                </small>
                            )}
                        </label>

                        {sendForm.channel === 'email' && (
                            <SendField
                                label="收件者 Email"
                                name="recipient"
                                type="email"
                                value={sendForm.recipient}
                                error={sendErrors.recipient?.[0]}
                                placeholder="customer@example.com"
                                required
                                onChange={handleSendFormChange}
                            />
                        )}

                        <SendField
                            label="排定派送時間（選填）"
                            name="scheduledAt"
                            type="datetime-local"
                            value={sendForm.scheduledAt}
                            error={sendErrors.scheduledAt?.[0]}
                            onChange={handleSendFormChange}
                        />
                    </div>

                    <label className="form-field">
                        <span>Data（JSON）</span>
                        <textarea
                            name="data"
                            value={sendForm.data}
                            rows="7"
                            disabled={sending}
                            onChange={handleSendFormChange}
                        />
                        {sendErrors.data && (
                            <small className="field-error">
                                {sendErrors.data[0]}
                            </small>
                        )}
                    </label>

                    {sendRequestError && (
                        <p className="modal-error">{sendRequestError}</p>
                    )}
                </form>
            </Modal>
        </div>
    );
}

function SendField({ label, error, ...inputProps }) {
    return (
        <label className="form-field">
            <span>{label}</span>
            <input {...inputProps} />
            {error && (
                <small className="field-error">{error}</small>
            )}
        </label>
    );
}
