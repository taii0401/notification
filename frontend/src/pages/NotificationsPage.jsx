import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';

import CustomSelect from '../components/ui/CustomSelect';
import Pagination from '../components/ui/Pagination';
import { getNotifications } from '../api/notifications';

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

    const [channelInput, setChannelInput] = useState('');
    const [statusInput, setStatusInput] = useState('');
    const [channel, setChannel] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);

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
    }, [uuid, channel, status, page]);

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
                                            />
                                        </td>
                                        <td>
                                            <DateTime
                                                value={notification.processed_at_display}
                                            />
                                        </td>
                                        <td>
                                            <DateTime
                                                value={notification.sent_at_display}
                                            />
                                        </td>
                                        <td>
                                            <DateTime
                                                value={notification.failed_at_display}
                                            />
                                        </td>
                                        <td>
                                            <div className="table-actions">
                                                <button
                                                    type="button"
                                                    className="table-action-button regenerate-button"
                                                    title="明細功能準備中"
                                                    disabled
                                                >
                                                    檢視
                                                </button>
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
        </div>
    );
}

function DateTime({ value }) {
    if (!value) {
        return '-';
    }

    const [date, time] = value.split(' ');

    return (
        <span className="table-date-time">
            <span>{date}</span>
            {time && <span>{time}</span>}
        </span>
    );
}
