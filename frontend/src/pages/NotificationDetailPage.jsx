import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';

import DateTime from '../components/ui/DateTime';
import { getNotification } from '../api/notifications';

export default function NotificationDetailPage() {
    const { uuid, notificationUuid } = useParams();
    const [notification, setNotification] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        let ignore = false;

        async function loadNotification() {
            setLoading(true);
            setError('');

            try {
                const result = await getNotification(
                    uuid,
                    notificationUuid
                );

                if (!ignore) {
                    setNotification(result);
                }
            } catch (requestError) {
                if (!ignore) {
                    console.error('Failed to load notification detail:', {
                        status: requestError.response?.status,
                        data: requestError.response?.data,
                    });
                    setNotification(null);
                    setError('通知明細載入失敗，請稍後再試。');
                }
            } finally {
                if (!ignore) {
                    setLoading(false);
                }
            }
        }

        loadNotification();

        return () => {
            ignore = true;
        };
    }, [uuid, notificationUuid]);

    if (loading) {
        return <div className="list-state">載入通知明細中...</div>;
    }

    if (error || !notification) {
        return (
            <div className="notification-detail-page">
                <div className="detail-page-header">
                    <h1>通知明細</h1>
                    <Link
                        className="secondary-button button-link"
                        to={`/projects/${uuid}/notifications`}
                    >
                        返回列表
                    </Link>
                </div>
                <div className="dashboard-card detail-empty-state">
                    {error || '找不到通知資料。'}
                </div>
            </div>
        );
    }

    return (
        <div className="notification-detail-page">
            <div className="detail-page-header">
                <div>
                    <h1>通知明細</h1>
                </div>

                <Link
                    className="secondary-button button-link"
                    to={`/projects/${uuid}/notifications`}
                >
                    返回列表
                </Link>
            </div>

            <section className="dashboard-card detail-section">
                <h2>通知資料</h2>
                <div className="detail-grid">
                    <DetailField
                        label="事件類型"
                        value={notification.event_type}
                    />
                    <DetailField
                        label="模板名稱"
                        value={notification.template?.name}
                    />
                    <DetailField
                        label="通知管道"
                        value={notification.channel_display ?? notification.channel}
                    />
                    <DetailField
                        label="收件者"
                        value={notification.recipient}
                    />
                    <DetailField label="通知狀態">
                        <StatusBadge status={notification.status} />
                    </DetailField>
                    <DetailField label="建立時間">
                        <DateTime value={notification.created_at_display} />
                    </DetailField>
                    <DetailField label="排定派送時間">
                        <DateTime value={notification.scheduled_at_display} />
                    </DetailField>
                    <DetailField label="開始處理時間">
                        <DateTime value={notification.processed_at_display} />
                    </DetailField>
                    <DetailField label="成功派送時間">
                        <DateTime value={notification.sent_at_display} />
                    </DetailField>
                    <DetailField label="最終失敗時間">
                        <DateTime value={notification.failed_at_display} />
                    </DetailField>
                </div>

                <JsonField label="Payload" value={notification.payload} />
                <JsonField label="Metadata" value={notification.metadata} />
            </section>

            {notification.deliveries?.length ? (
                notification.deliveries.map((delivery, deliveryIndex) => (
                    <DeliverySection
                        key={delivery.id}
                        delivery={delivery}
                        index={deliveryIndex}
                    />
                ))
            ) : (
                <div className="dashboard-card detail-empty-state">
                    尚無派送資料。
                </div>
            )}
        </div>
    );
}

function DeliverySection({ delivery, index }) {
    return (
        <section className="dashboard-card detail-section">
            <div className="detail-section-header">
                <div>
                    <h2>派送 #{index + 1}</h2>
                    <p>{delivery.provider ?? '-'}</p>
                </div>
                <StatusBadge status={delivery.status} />
            </div>

            <div className="detail-grid">
                <DetailField
                    label="發送次數"
                    value={delivery.attempt_count}
                />
                <DetailField label="建立時間">
                    <DateTime value={delivery.created_at_display} />
                </DetailField>
                <DetailField label="成功時間">
                    <DateTime value={delivery.sent_at_display} />
                </DetailField>
                <DetailField label="最終失敗時間">
                    <DateTime value={delivery.failed_at_display} />
                </DetailField>
            </div>

            <h3 className="detail-subtitle">發送歷程</h3>

            {delivery.attempts?.length ? (
                <div className="attempt-list">
                    {delivery.attempts.map((attempt) => (
                        <AttemptCard key={attempt.id} attempt={attempt} />
                    ))}
                </div>
            ) : (
                <div className="detail-empty-state">尚無發送歷程。</div>
            )}
        </section>
    );
}

function AttemptCard({ attempt }) {
    return (
        <article className="attempt-card">
            <div className="attempt-card-header">
                <strong>第 {attempt.attempt_no} 次嘗試</strong>
                <StatusBadge status={attempt.status} />
            </div>

            <div className="detail-grid">
                <DetailField label="開始時間">
                    <DateTime value={attempt.started_at_display} />
                </DetailField>
                <DetailField label="完成時間">
                    <DateTime value={attempt.finished_at_display} />
                </DetailField>
                <DetailField
                    label="HTTP 狀態碼"
                    value={attempt.response_code}
                />
                <DetailField
                    label="錯誤類型"
                    value={attempt.error_type}
                />
                <DetailField
                    className="detail-field-wide"
                    label="錯誤訊息"
                    value={attempt.error_message}
                />
            </div>

            <JsonField
                label="Request Payload"
                value={attempt.request_payload}
            />
            <JsonField
                label="Response Body"
                value={attempt.response_body}
            />
        </article>
    );
}

function DetailField({ label, value, children, className = '' }) {
    return (
        <div className={`detail-field ${className}`.trim()}>
            <span>{label}</span>
            <div>{children ?? displayValue(value)}</div>
        </div>
    );
}

function JsonField({ label, value }) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const content = typeof value === 'string'
        ? value
        : JSON.stringify(value, null, 2);

    return (
        <div className="detail-json-field">
            <span>{label}</span>
            <pre>{content}</pre>
        </div>
    );
}

function StatusBadge({ status }) {
    return (
        <span className={`badge ${statusBadgeClass(status)}`}>
            {statusLabel(status)}
        </span>
    );
}

function displayValue(value) {
    return value === null || value === undefined || value === ''
        ? '-'
        : value;
}

function statusLabel(status) {
    const labels = {
        pending: '待處理',
        queued: '已排入佇列',
        processing: '處理中',
        sent: '派送成功',
        success: '成功',
        failed: '失敗',
    };

    return labels[status] ?? status ?? '-';
}

function statusBadgeClass(status) {
    if (status === 'success' || status === 'sent') {
        return 'badge-success';
    }

    if (status === 'failed') {
        return 'badge-danger';
    }

    if (status === 'pending' || status === 'queued') {
        return 'badge-warning';
    }

    return 'badge-processing';
}
