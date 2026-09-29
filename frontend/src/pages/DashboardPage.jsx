import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';

import { getDashboardStats, getDeliveryChart } from '../api/dashboard';

function toMonthKey(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

function shiftMonth(monthKey, amount) {
    const [year, month] = monthKey.split('-').map(Number);

    return toMonthKey(new Date(year, month - 1 + amount, 1));
}

const recentNotifications = [
    { event: 'order.paid', channel: 'Email', time: '2 min ago', status: 'Sent', tone: 'sent' },
    { event: 'user.registered', channel: 'Webhook', time: '8 min ago', status: 'Sent', tone: 'sent' },
    { event: 'payment.failed', channel: 'Email', time: '14 min ago', status: 'Failed', tone: 'failed' },
];

const recentFailures = [
    { event: 'payment.failed', error: 'Connection timeout', time: '14 min ago' },
    { event: 'webhook.failed', error: 'HTTP 500 response', time: '32 min ago' },
    { event: 'order.failed', error: 'Connection timeout', time: '1 hr ago' },
];

function DeliveryChart({ chartData, error, loading, monthKey }) {
    const monthLabel = monthKey;
    const chartWidth = 760;
    const chartHeight = 260;
    const plot = { left: 52, right: 744, top: 20, bottom: 216 };
    const highestValue = Math.max(...chartData.map((item) => item.notificationCount));
    const maxValue = Math.max(4, Math.ceil(highestValue / 4) * 4);
    const yTicks = Array.from({ length: 5 }, (_, index) => (maxValue / 4) * index);
    const xForIndex = (index) => plot.left + (index * (plot.right - plot.left)) / (chartData.length - 1);
    const yForValue = (value) => plot.bottom - (value / maxValue) * (plot.bottom - plot.top);
    const pointsFor = (field) => chartData
        .map((item, index) => `${xForIndex(index)},${yForValue(item[field])}`)
        .join(' ');
    const labelDays = new Set([1, 5, 10, 15, 20, 25, chartData.length]);

    return (
        <div className="delivery-chart">
            <div className="chart-legend" aria-label="Chart legend">
                <span><i className="chart-legend-dot chart-legend-dot-total" />發送</span>
                <span><i className="chart-legend-dot chart-legend-dot-success" />成功</span>
            </div>
            {loading && <div className="chart-state">Loading delivery data...</div>}
            {!loading && error && <div className="chart-state chart-state-error">{error}</div>}
            {!loading && !error && (
            <svg viewBox={`0 0 ${chartWidth} ${chartHeight}`} role="img" aria-label={`Daily notification and success counts for ${monthLabel}`}>
                <title>{`Daily delivery volume for ${monthLabel}`}</title>

                {yTicks.map((value) => {
                    const y = yForValue(value);

                    return (
                        <g key={value}>
                            <line className="chart-grid-line" x1={plot.left} x2={plot.right} y1={y} y2={y} />
                            <text className="chart-axis-label" x={plot.left - 10} y={y + 4} textAnchor="end">
                                {Math.round(value)}
                            </text>
                        </g>
                    );
                })}

                <polyline className="chart-line chart-line-success" points={pointsFor('successCount')} />
                <polyline className="chart-line chart-line-total" points={pointsFor('notificationCount')} />

                {chartData.map((item, index) => {
                    const x = xForIndex(index);

                    return (
                        <g key={item.day}>
                            <circle className="chart-point chart-point-total" cx={x} cy={yForValue(item.notificationCount)} r="4.5">
                                <title>{`${monthLabel} ${item.day}: ${item.notificationCount} notifications`}</title>
                            </circle>
                            <circle className="chart-point chart-point-success" cx={x} cy={yForValue(item.successCount)} r="4.5">
                                <title>{`${monthLabel} ${item.day}: ${item.successCount} successful`}</title>
                            </circle>
                            {labelDays.has(item.day) && (
                                <text className="chart-axis-label" x={x} y={plot.bottom + 24} textAnchor="middle">
                                    {item.day}
                                </text>
                            )}
                        </g>
                    );
                })}

                <text className="chart-axis-title" x={(plot.left + plot.right) / 2} y={chartHeight - 2} textAnchor="middle">
                    Date
                </text>
            </svg>
            )}
        </div>
    );
}

export default function DashboardPage() {
    const { uuid } = useParams();
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [selectedMonth, setSelectedMonth] = useState(() => toMonthKey(new Date()));
    const [chartData, setChartData] = useState([]);
    const [chartLoading, setChartLoading] = useState(true);
    const [chartError, setChartError] = useState('');
    const notificationsPath = `/projects/${uuid}/notifications`;
    const currentMonth = toMonthKey(new Date());

    useEffect(() => {
        let ignore = false;

        async function loadStats() {
            setLoading(true);
            setError('');

            try {
                const data = await getDashboardStats(uuid);

                if (!ignore) {
                    setStats(data);
                }
            } catch (requestError) {
                console.error('Failed to load dashboard:', requestError);

                if (!ignore) {
                    setError('Unable to load dashboard statistics.');
                }
            } finally {
                if (!ignore) {
                    setLoading(false);
                }
            }
        }

        loadStats();

        return () => {
            ignore = true;
        };
    }, [uuid]);

    useEffect(() => {
        let ignore = false;

        async function loadDeliveryChart() {
            setChartLoading(true);
            setChartError('');

            try {
                const data = await getDeliveryChart(uuid, selectedMonth);

                if (!ignore) {
                    setChartData(data.daily.map((item) => ({
                        day: Number(item.date.slice(-2)),
                        notificationCount: Number(item.total),
                        successCount: Number(item.success),
                    })));
                }
            } catch (requestError) {
                console.error('Failed to load delivery chart:', requestError);

                if (!ignore) {
                    setChartData([]);
                    setChartError('Unable to load delivery data.');
                }
            } finally {
                if (!ignore) {
                    setChartLoading(false);
                }
            }
        }

        loadDeliveryChart();

        return () => {
            ignore = true;
        };
    }, [selectedMonth, uuid]);

    if (loading) {
        return <div className="list-state">Loading dashboard...</div>;
    }

    if (error || !stats) {
        return <div className="list-state">{error || 'Dashboard statistics are unavailable.'}</div>;
    }

    const overviewStats = [
        {
            label: '發送',
            value: stats.total.toLocaleString(),
            description: 'All notifications',
            tone: 'primary',
        },
        {
            label: '成功',
            value: stats.sent.toLocaleString(),
            description: 'Successfully delivered',
            tone: 'success',
        },
        {
            label: '失敗',
            value: stats.failed.toLocaleString(),
            description: 'Failed delivered',
            tone: 'danger',
        },
        {
            label: '成功率',
            value: `${Number(stats.success_rate).toFixed(1)}%`,
            description: 'Completed deliveries',
            tone: 'accent',
        },
    ];

    const pipeline = [
        { label: '等待發送', value: stats.waiting, tone: 'waiting' },
        { label: '正在發送', value: stats.processing, tone: 'processing' },
    ];

    return (
        <div className="dashboard">
            <section className="stats-grid" aria-label="Notification summary">
                {overviewStats.map((stat) => (
                    <article className={`stat-card stat-card-${stat.tone}`} key={stat.label}>
                        <span className="stat-label">{stat.label}</span>
                        <strong className="stat-value">{stat.value}</strong>
                        <span className="stat-description">{stat.description}</span>
                    </article>
                ))}
            </section>

            <section className="dashboard-section" aria-labelledby="pipeline-title">
                <div className="pipeline-card">
                    {pipeline.map((stage) => (
                        <div className="pipeline-stage-wrap" key={stage.label}>
                            <div className={`pipeline-stage pipeline-stage-${stage.tone}`}>
                                <span className="pipeline-indicator" aria-hidden="true" />
                                <div>
                                    <span className="pipeline-label">{stage.label}</span>
                                    <strong>{stage.value.toLocaleString()}</strong>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            <section className="dashboard-card delivery-volume-card" aria-labelledby="volume-title">
                <div className="card-header dashboard-card-header">
                    <div>
                        <h2 id="volume-title"></h2>
                    </div>
                    <nav className="month-navigation" aria-label="Delivery chart month">
                        <button
                            type="button"
                            aria-label="Previous month"
                            onClick={() => setSelectedMonth((month) => shiftMonth(month, -1))}
                        >
                            <span aria-hidden="true">←</span>
                        </button>
                        <strong aria-live="polite">{selectedMonth}</strong>
                        <button
                            type="button"
                            aria-label="Next month"
                            disabled={selectedMonth >= currentMonth}
                            onClick={() => setSelectedMonth((month) => shiftMonth(month, 1))}
                        >
                            <span aria-hidden="true">→</span>
                        </button>
                    </nav>
                </div>
                <DeliveryChart
                    chartData={chartData}
                    error={chartError}
                    loading={chartLoading}
                    monthKey={selectedMonth}
                />
            </section>

            {/* <div className="dashboard-lists">
                <section className="dashboard-card" aria-labelledby="recent-title">
                    <div className="card-header dashboard-card-header">
                        <div>
                            <h2 id="recent-title">Recent Notifications</h2>
                            <p>Latest delivery activity.</p>
                        </div>
                    </div>
                    <div className="activity-list">
                        {recentNotifications.map((notification) => (
                            <div className="activity-row" key={`${notification.event}-${notification.time}`}>
                                <div className="activity-main">
                                    <strong>{notification.event}</strong>
                                    <span>{notification.channel} · {notification.time}</span>
                                </div>
                                <span className={`badge badge-${notification.tone}`}>{notification.status}</span>
                            </div>
                        ))}
                    </div>
                    <Link className="dashboard-card-link" to={notificationsPath}>
                        View all notifications <span aria-hidden="true">→</span>
                    </Link>
                </section>

                <section className="dashboard-card" aria-labelledby="failures-title">
                    <div className="card-header dashboard-card-header">
                        <div>
                            <h2 id="failures-title">Recent Failures</h2>
                            <p>Deliveries that need attention.</p>
                        </div>
                        <span className="failure-count">3</span>
                    </div>
                    <div className="activity-list">
                        {recentFailures.map((failure) => (
                            <div className="activity-row failure-row" key={`${failure.event}-${failure.time}`}>
                                <span className="failure-icon" aria-hidden="true">!</span>
                                <div className="activity-main">
                                    <strong>{failure.event}</strong>
                                    <span>{failure.error} · {failure.time}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                    <Link className="dashboard-card-link" to={`${notificationsPath}?status=failed`}>
                        View all failures <span aria-hidden="true">→</span>
                    </Link>
                </section>
            </div> */}
        </div>
    );
}
