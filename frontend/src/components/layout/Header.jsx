import { useEffect, useState } from 'react';
import { useLocation, useParams } from 'react-router-dom';
import { getProject } from '../../api/projects';

export default function Topbar() {
    const { uuid } = useParams();
    const location = useLocation();
    //console.log('[Header render] route uuid:', uuid);

    //預設
    const [project, setProject] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        async function loadProject() {
            if (!uuid) {
                setLoading(false);
                return;
            }

            const data = await getProject(uuid);
            setProject(data);
            setLoading(false);
        }

        loadProject();
    }, [uuid]);

    let pageTitle = '儀錶板';
    if (location.pathname.includes('/notifications')) {
        pageTitle = '通知';
    } else if (location.pathname.includes('/templates')) {
        pageTitle = '模板';
    } else if (location.pathname.includes('/api-keys')) {
        pageTitle = 'API Keys';
    }

    return (
        <header className="topbar">
            <div className="topbar-title">
                {pageTitle}
            </div>

            <div className="topbar-right">
                {loading
                    ? 'Loading project...'
                    : project?.name ?? 'Project not found'
                }
            </div>
        </header>
    );
}