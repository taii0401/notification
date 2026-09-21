export default function Sidebar() {
    return (
        <aside className="sidebar">
            <div className="sidebar-brand">
                <img
                    src="/images/logo.png"
                    alt="Glidery Studio"
                    className="sidebar-logo"
                />

                <div className="sidebar-brand-text">
                    <strong>Glidery Studio</strong>
                    <span>Notification</span>
                </div>
            </div>

            <nav className="sidebar-nav">
                <a className="nav-item active" href="#">
                    Dashboard
                </a>

                <a className="nav-item" href="#">
                    Notifications
                </a>

                <a className="nav-item" href="#">
                    Templates
                </a>

                <a className="nav-item" href="#">
                    API Keys
                </a>

                <a className="nav-item" href="#">
                    Projects
                </a>
            </nav>
        </aside>
    );
}