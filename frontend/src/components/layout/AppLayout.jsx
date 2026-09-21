import Sidebar from './Sidebar';
import Header from './Header';

export default function AppLayout({ children }) {
    return (
        <div className="app-layout">
            <Sidebar />

            <div className="app-main">
                <Header />

                <main className="app-content">
                    
                </main>
            </div>
        </div>
    );
}