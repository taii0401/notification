import {
    BrowserRouter,
    Navigate,
    Route,
    Routes,
} from 'react-router-dom';

import AppLayout from './components/layout/AppLayout';
import DashboardPage from './pages/DashboardPage';
import ApiKeysPage from './pages/ApiKeysPage';
import TemplatesPage from './pages/TemplatesPage';

export default function App() {
    return (
        <BrowserRouter>
            <Routes>
                <Route element={<AppLayout />}>
                    <Route
                        path="/projects/:uuid"
                        element={<DashboardPage />}
                    />
                    <Route
                        path="/projects/:uuid/api-keys"
                        element={<ApiKeysPage />}
                    />
                    <Route
                        path="/projects/:uuid/templates"
                        element={<TemplatesPage />}
                    />
                </Route>
            </Routes>
        </BrowserRouter>
    );
}