import { useEffect, useState } from 'react';
import { getDashboardStats } from '../api/dashboard';

export default function DashboardPage() {
    const [stats, setStats] = useState(null);

    useEffect(() => {
        async function loadDashboard() {
            const data = await getDashboardStats();
            setStats(data);
        }
        loadDashboard();
    }, []);

    if (!stats) {
        return <div>Loading dashboard...</div>;
    }

    return (
        <div className="dashboard">
            <div className="page-header">
                <div>
                    <p className="page-eyebrow">
                        Overview
                    </p>

                    <h1>Notification Dashboard</h1>

                    <p className="page-description">
                        Monitor notification delivery and system activity.
                    </p>
                </div>

                <button className="secondary-button">
                    Last 7 days
                </button>
            </div>

            <div className="stats-grid">
                {statCards.map((stat) => (
                    <div className="stat-card" key={stat.label}>
                        <span className="stat-label">
                            {stat.label}
                        </span>

                        <strong className="stat-value">
                            {stat.value}
                        </strong>

                        <span className="stat-description">
                            {stat.description}
                        </span>
                    </div>
                ))}
            </div>

            <div className="dashboard-card">
                <div className="card-header">
                    <div>
                        <h2>Delivery Volume</h2>
                        <p>Notification activity over the last 7 days.</p>
                    </div>
                </div>

                <div className="chart-placeholder">
                    Delivery chart
                </div>
            </div>

            <div className="dashboard-card">
                <div className="card-header">
                    <div>
                        <h2>Recent Notifications</h2>
                        <p>Latest notification delivery activity.</p>
                    </div>
                </div>

                <table className="notification-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Channel</th>
                            <th>Recipient</th>
                            <th>Status</th>
                            <th>Attempts</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>order.paid</td>
                            <td>Email</td>
                            <td>a***@gmail.com</td>
                            <td>
                                <span className="badge badge-success">
                                    Sent
                                </span>
                            </td>
                            <td>1</td>
                        </tr>

                        <tr>
                            <td>user.registered</td>
                            <td>Webhook</td>
                            <td>webhook.site</td>
                            <td>
                                <span className="badge badge-success">
                                    Sent
                                </span>
                            </td>
                            <td>2</td>
                        </tr>

                        <tr>
                            <td>payment.failed</td>
                            <td>Email</td>
                            <td>b***@yahoo.com</td>
                            <td>
                                <span className="badge badge-danger">
                                    Failed
                                </span>
                            </td>
                            <td>4</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    );
}