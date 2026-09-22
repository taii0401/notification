import { NavLink, useParams } from 'react-router-dom';

export default function Sidebar() {
    const { uuid } = useParams();

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
                </div>
            </div>

            <nav className="sidebar-nav">
                <NavLink
                    to={`/projects/${uuid}`}
                    end
                    className={({ isActive }) =>
                        isActive
                            ? 'nav-item active'
                            : 'nav-item'
                    }
                >
                    儀錶板
                </NavLink>

                <NavLink
                    to={`/projects/${uuid}/notifications`}
                    className={({ isActive }) =>
                        isActive
                            ? 'nav-item active'
                            : 'nav-item'
                    }
                >
                    通知
                </NavLink>                

                <NavLink
                    to={`/projects/${uuid}/api-keys`}
                    className={({ isActive }) =>
                        isActive
                            ? 'nav-item active'
                            : 'nav-item'
                    }
                >
                    API Keys
                </NavLink>

                <NavLink
                    to={`/projects/${uuid}/templates`}
                    className={({ isActive }) =>
                        isActive
                            ? 'nav-item active'
                            : 'nav-item'
                    }
                >
                    模板
                </NavLink>
            </nav>
        </aside>
    );
}