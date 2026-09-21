import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';

import Modal from '../components/ui/Modal';
import Pagination from '../components/ui/Pagination';
import {
    createApiKey,
    deleteApiKey,
    getApiKeys,
    regenerateApiKey,
} from '../api/apiKeys';

const initialPagination = {
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
    from: null,
    to: null,
};

export default function ApiKeysPage() {
    const { uuid } = useParams();

    const [apiKeys, setApiKeys] = useState([]);
    const [pagination, setPagination] = useState(initialPagination);
    const [loading, setLoading] = useState(true);
    const [reloadToken, setReloadToken] = useState(0);

    const [keywordInput, setKeywordInput] = useState('');
    const [keyword, setKeyword] = useState('');
    const [page, setPage] = useState(1);

    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [createName, setCreateName] = useState('');
    const [createExpiresAt, setCreateExpiresAt] = useState('');
    const [createErrors, setCreateErrors] = useState({});
    const [creating, setCreating] = useState(false);

    const [regenerateTarget, setRegenerateTarget] = useState(null);
    const [regenerating, setRegenerating] = useState(false);

    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const [revealedKey, setRevealedKey] = useState(null);
    const [copyStatus, setCopyStatus] = useState('idle');

    useEffect(() => {
        let ignore = false;

        async function loadApiKeys() {
            setLoading(true);

            try {
                const result = await getApiKeys(uuid, {
                    keyword,
                    page,
                });

                if (!ignore) {
                    setApiKeys(result.data);
                    setPagination(result.meta);
                }
            } catch (error) {
                if (!ignore) {
                    console.error('Failed to load API keys:', {
                        uuid,
                        status: error.response?.status,
                        data: error.response?.data,
                    });
                    setApiKeys([]);
                    setPagination(initialPagination);
                }
            } finally {
                if (!ignore) {
                    setLoading(false);
                }
            }
        }

        loadApiKeys();

        return () => {
            ignore = true;
        };
    }, [uuid, keyword, page, reloadToken]);

    function handleSearch(event) {
        event.preventDefault();
        setKeyword(keywordInput.trim());
        setPage(1);
    }

    function handleClearSearch() {
        setKeywordInput('');
        setKeyword('');
        setPage(1);
    }

    function openCreateModal() {
        setCreateErrors({});
        setCreateModalOpen(true);
    }

    function closeCreateModal() {
        if (creating) {
            return;
        }

        setCreateModalOpen(false);
        setCreateName('');
        setCreateExpiresAt('');
        setCreateErrors({});
    }

    async function handleCreateApiKey(event) {
        event.preventDefault();

        try {
            setCreating(true);
            setCreateErrors({});

            const data = await createApiKey(uuid, {
                name: createName.trim(),
                expires_at: createExpiresAt
                    ? new Date(createExpiresAt).toISOString()
                    : null,
            });

            setCreateModalOpen(false);
            setCreateName('');
            setCreateExpiresAt('');
            setKeywordInput('');
            setKeyword('');
            setPage(1);
            setReloadToken((value) => value + 1);
            showRevealedKey(data);
        } catch (error) {
            if (error.response?.status === 422) {
                setCreateErrors(error.response.data.errors ?? {});
            } else {
                console.error('Failed to create API key:', error);
            }
        } finally {
            setCreating(false);
        }
    }

    async function handleRegenerate() {
        if (!regenerateTarget) {
            return;
        }

        try {
            setRegenerating(true);
            const data = await regenerateApiKey(
                uuid,
                regenerateTarget.id
            );

            setRegenerateTarget(null);
            showRevealedKey(data);
        } catch (error) {
            console.error('Failed to regenerate API key:', {
                status: error.response?.status,
                data: error.response?.data,
            });
        } finally {
            setRegenerating(false);
        }
    }

    async function handleDelete() {
        if (!deleteTarget) {
            return;
        }

        try {
            setDeleting(true);
            await deleteApiKey(uuid, deleteTarget.id);
            setDeleteTarget(null);

            if (apiKeys.length === 1 && page > 1) {
                setPage((current) => current - 1);
            } else {
                setReloadToken((value) => value + 1);
            }
        } catch (error) {
            console.error('Failed to delete API key:', {
                status: error.response?.status,
                data: error.response?.data,
            });
        } finally {
            setDeleting(false);
        }
    }

    function showRevealedKey(data) {
        setCopyStatus('idle');
        setRevealedKey({
            name: data.name,
            apiKey: data.api_key,
        });
    }

    function closeRevealedKey() {
        setRevealedKey(null);
        setCopyStatus('idle');
    }

    async function handleCopyKey() {
        if (!revealedKey) {
            return;
        }

        try {
            await navigator.clipboard.writeText(revealedKey.apiKey);
            setCopyStatus('copied');
        } catch (error) {
            console.error('Failed to copy API key:', error);
            setCopyStatus('failed');
        }
    }

    return (
        <div className="api-keys-page">
            <div className="list-toolbar">
                <form className="list-search" onSubmit={handleSearch}>
                    <input
                        type="search"
                        value={keywordInput}
                        placeholder="搜尋名稱"
                        aria-label="搜尋 API Key 名稱"
                        onChange={(event) =>
                            setKeywordInput(event.target.value)
                        }
                    />

                    <button type="submit" className="secondary-button">
                        搜尋
                    </button>

                    {keyword && (
                        <button
                            type="button"
                            className="text-button"
                            onClick={handleClearSearch}
                        >
                            清除
                        </button>
                    )}
                </form>

                <button
                    type="button"
                    className="primary-button"
                    onClick={openCreateModal}
                >
                    建立
                </button>
            </div>

            <div className="dashboard-card list-card">
                {loading ? (
                    <div className="list-state">載入中...</div>
                ) : (
                    <table className="notification-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>名稱</th>
                                <th>Key</th>
                                <th>建立時間</th>
                                <th>操作</th>
                            </tr>
                        </thead>

                        <tbody>
                            {apiKeys.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="list-state">
                                        目前無資料
                                    </td>
                                </tr>
                            ) : (
                                apiKeys.map((apiKey, index) => (
                                    <tr key={apiKey.id}>
                                        <td>
                                            {(pagination.current_page - 1) *
                                                pagination.per_page +
                                                index +
                                                1}
                                        </td>
                                        <td>{apiKey.name}</td>
                                        <td>
                                            <code className="api-key-prefix">
                                                {apiKey.key_prefix ?? ''}********
                                            </code>
                                        </td>
                                        <td>{apiKey.created_at_display}</td>
                                        <td>
                                            <div className="table-actions">
                                                <button
                                                    type="button"
                                                    className="table-action-button regenerate-button"
                                                    onClick={() =>
                                                        setRegenerateTarget(apiKey)
                                                    }
                                                >
                                                    重新產生
                                                </button>

                                                <button
                                                    type="button"
                                                    className="table-action-button delete-button"
                                                    onClick={() =>
                                                        setDeleteTarget(apiKey)
                                                    }
                                                >
                                                    刪除
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

            <Modal
                open={createModalOpen}
                title="建立 API Key"
                closeDisabled={creating}
                onClose={closeCreateModal}
                actions={(
                    <>
                        <button
                            type="button"
                            className="secondary-button"
                            disabled={creating}
                            onClick={closeCreateModal}
                        >
                            取消
                        </button>

                        <button
                            type="submit"
                            form="create-api-key-form"
                            className="primary-button"
                            disabled={creating}
                        >
                            {creating ? '建立中...' : '確認建立'}
                        </button>
                    </>
                )}
            >
                <form
                    id="create-api-key-form"
                    onSubmit={handleCreateApiKey}
                >
                    <label className="form-field">
                        <span>名稱</span>
                        <input
                            type="text"
                            value={createName}
                            maxLength={100}
                            required
                            autoFocus
                            onChange={(event) =>
                                setCreateName(event.target.value)
                            }
                        />
                        {createErrors.name && (
                            <small className="field-error">
                                {createErrors.name[0]}
                            </small>
                        )}
                    </label>

                    <label className="form-field">
                        <span>到期時間（選填）</span>
                        <input
                            type="datetime-local"
                            value={createExpiresAt}
                            onChange={(event) =>
                                setCreateExpiresAt(event.target.value)
                            }
                        />
                        {createErrors.expires_at && (
                            <small className="field-error">
                                {createErrors.expires_at[0]}
                            </small>
                        )}
                    </label>
                </form>
            </Modal>

            <Modal
                open={Boolean(regenerateTarget)}
                title="確認重新產生 API Key？"
                closeDisabled={regenerating}
                onClose={() => setRegenerateTarget(null)}
                actions={(
                    <>
                        <button
                            type="button"
                            className="secondary-button"
                            disabled={regenerating}
                            onClick={() => setRegenerateTarget(null)}
                        >
                            取消
                        </button>

                        <button
                            type="button"
                            className="primary-button"
                            disabled={regenerating}
                            onClick={handleRegenerate}
                        >
                            {regenerating
                                ? '重新產生中...'
                                : '確認重新產生'}
                        </button>
                    </>
                )}
            >
                <p>
                    即將重新產生「{regenerateTarget?.name}」的 API Key。
                </p>
                <p className="modal-warning">
                    舊的 API Key 將立即失效，此操作無法復原。
                </p>
            </Modal>

            <Modal
                open={Boolean(deleteTarget)}
                title="確認刪除 API Key？"
                closeDisabled={deleting}
                onClose={() => setDeleteTarget(null)}
                actions={(
                    <>
                        <button
                            type="button"
                            className="secondary-button"
                            disabled={deleting}
                            onClick={() => setDeleteTarget(null)}
                        >
                            取消
                        </button>

                        <button
                            type="button"
                            className="table-action-button delete-button"
                            disabled={deleting}
                            onClick={handleDelete}
                        >
                            {deleting ? '刪除中...' : '確認刪除'}
                        </button>
                    </>
                )}
            >
                <p>確定要刪除「{deleteTarget?.name}」嗎？</p>
                <p className="modal-warning">
                    刪除後這組 API Key 將無法繼續使用。
                </p>
            </Modal>

            <Modal
                open={Boolean(revealedKey)}
                title={`New API Key for ${revealedKey?.name ?? ''}`}
                closeDisabled
                onClose={closeRevealedKey}
                actions={(
                    <>
                        <button
                            type="button"
                            className="secondary-button"
                            onClick={handleCopyKey}
                        >
                            {copyStatus === 'copied'
                                ? '已複製'
                                : '複製 Key'}
                        </button>

                        <button
                            type="button"
                            className="primary-button"
                            onClick={closeRevealedKey}
                        >
                            關閉視窗
                        </button>
                    </>
                )}
            >
                <code className="regenerated-key-value">
                    {revealedKey?.apiKey}
                </code>
                <p className="modal-warning">
                    請立即保存。關閉後將無法再次查看。
                </p>
                {copyStatus === 'failed' && (
                    <p className="modal-error">
                        複製失敗，請手動選取 Key。
                    </p>
                )}
            </Modal>
        </div>
    );
}
