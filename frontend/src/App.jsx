import {
    BrowserRouter,
    Navigate,
    Route,
    Routes,
} from 'react-router-dom';

import AppLayout from './components/layout/AppLayout';
import ApiKeysPage from './pages/ApiKeysPage';
import DashboardPage from './pages/DashboardPage';

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
                </Route>
            </Routes>
        </BrowserRouter>
    );
}