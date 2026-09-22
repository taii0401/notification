import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';

import Modal from '../components/ui/Modal';
import Pagination from '../components/ui/Pagination';
import CustomSelect from '../components/ui/CustomSelect';
import {
    createTemplate,
    deleteTemplate,
    getTemplates,
    updateTemplate,
} from '../api/templates';

const initialPagination = {
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
    from: null,
    to: null,
};

const emptyForm = {
    code: '',
    name: '',
    channel: '',
    subject: '',
    content: '',
    status: 'active',
};

export default function TemplatesPage() {
    const { uuid } = useParams();

    const [templates, setTemplates] = useState([]);
    const [channelOptions, setChannelOptions] = useState([]);
    const [pagination, setPagination] = useState(initialPagination);
    const [loading, setLoading] = useState(true);
    const [reloadToken, setReloadToken] = useState(0);

    const [keywordInput, setKeywordInput] = useState('');
    const [statusInput, setStatusInput] = useState('');
    const [keyword, setKeyword] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);

    const [formMode, setFormMode] = useState(null);
    const [editingTemplate, setEditingTemplate] = useState(null);
    const [form, setForm] = useState(emptyForm);
    const [formErrors, setFormErrors] = useState({});
    const [saving, setSaving] = useState(false);

    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deleting, setDeleting] = useState(false);

    useEffect(() => {
        let ignore = false;

        async function loadTemplates() {
            setLoading(true);

            try {
                const result = await getTemplates(uuid, {
                    keyword,
                    status,
                    page,
                });

                if (!ignore) {
                    setTemplates(result.data);
                    setPagination(result.meta);
                    setChannelOptions(result.options?.channels ?? []);
                }
            } catch (error) {
                if (!ignore) {
                    console.error('Failed to load templates:', {
                        status: error.response?.status,
                        data: error.response?.data,
                    });
                    setTemplates([]);
                    setPagination(initialPagination);
                }
            } finally {
                if (!ignore) {
                    setLoading(false);
                }
            }
        }

        loadTemplates();

        return () => {
            ignore = true;
        };
    }, [uuid, keyword, status, page, reloadToken]);

    function handleSearch(event) {
        event.preventDefault();
        setKeyword(keywordInput.trim());
        setStatus(statusInput);
        setPage(1);
    }

    function handleClearSearch() {
        setKeywordInput('');
        setStatusInput('');
        setKeyword('');
        setStatus('');
        setPage(1);
    }

    function handleFormChange(event) {
        const { name, value } = event.target;

        setForm((current) => ({
            ...current,
            [name]: value,
        }));

        setFormErrors((current) => ({
            ...current,
            [name]: undefined,
        }));
    }

    function handleFormValueChange(name, value) {
        setForm((current) => ({
            ...current,
            [name]: value,
        }));

        setFormErrors((current) => ({
            ...current,
            [name]: undefined,
        }));
    }

    function openCreateModal() {
        setFormMode('create');
        setEditingTemplate(null);
        setForm({
            ...emptyForm,
            channel: channelOptions[0]?.value ?? '',
        });
        setFormErrors({});
    }

    function openEditModal(template) {
        setFormMode('edit');
        setEditingTemplate(template);
        setForm({
            code: template.code ?? '',
            name: template.name ?? '',
            channel: template.channel ?? 'email',
            subject: template.subject ?? '',
            content: template.content ?? '',
            status: template.status ?? 'active',
        });
        setFormErrors({});
    }

    function closeFormModal() {
        if (saving) {
            return;
        }

        setFormMode(null);
        setEditingTemplate(null);
        setForm(emptyForm);
        setFormErrors({});
    }

    async function handleSave(event) {
        event.preventDefault();

        const payload = {
            code: form.code.trim(),
            name: form.name.trim(),
            channel: form.channel,
            subject: form.subject.trim() || null,
            content: form.content,
            status: form.status,
        };

        try {
            setSaving(true);
            setFormErrors({});

            if (formMode === 'edit') {
                await updateTemplate(
                    uuid,
                    editingTemplate.id,
                    payload
                );
            } else {
                await createTemplate(uuid, payload);
                setPage(1);
            }

            closeFormModalAfterSave();
            setReloadToken((value) => value + 1);
        } catch (error) {
            if (error.response?.status === 422) {
                setFormErrors(error.response.data.errors ?? {});
            } else {
                console.error('Failed to save template:', {
                    status: error.response?.status,
                    data: error.response?.data,
                });
            }
        } finally {
            setSaving(false);
        }
    }

    function closeFormModalAfterSave() {
        setFormMode(null);
        setEditingTemplate(null);
        setForm(emptyForm);
        setFormErrors({});
    }

    async function handleDelete() {
        if (!deleteTarget) {
            return;
        }

        try {
            setDeleting(true);
            await deleteTemplate(uuid, deleteTarget.id);
            setDeleteTarget(null);

            if (templates.length === 1 && page > 1) {
                setPage((current) => current - 1);
            } else {
                setReloadToken((value) => value + 1);
            }
        } catch (error) {
            console.error('Failed to delete template:', {
                status: error.response?.status,
                data: error.response?.data,
            });
        } finally {
            setDeleting(false);
        }
    }

    return (
        <div className="templates-page">
            <div className="list-toolbar">
                <form className="list-search" onSubmit={handleSearch}>
                    <input
                        type="search"
                        value={keywordInput}
                        placeholder="搜尋名稱或代碼"
                        aria-label="搜尋模板名稱或代碼"
                        onChange={(event) =>
                            setKeywordInput(event.target.value)
                        }
                    />

                    <CustomSelect
                        className="list-filter-custom-select"
                        value={statusInput}
                        ariaLabel="狀態篩選"
                        options={[
                            { value: '', label: '全部狀態' },
                            { value: 'active', label: '啟用' },
                            { value: 'inactive', label: '停用' },
                        ]}
                        onChange={setStatusInput}
                    />

                    <button type="submit" className="secondary-button">
                        搜尋
                    </button>

                    {(keyword || status) && (
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
                    新增
                </button>
            </div>

            <div className="dashboard-card list-card">
                {loading ? (
                    <div className="list-state">載入中...</div>
                ) : (
                    <table className="notification-table template-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>名稱</th>
                                <th>代碼</th>
                                <th>通知管道</th>
                                <th>狀態</th>
                                <th>更新時間</th>
                                <th>操作</th>
                            </tr>
                        </thead>

                        <tbody>
                            {templates.length === 0 ? (
                                <tr>
                                    <td colSpan="7" className="empty-state">
                                        目前無資料
                                    </td>
                                </tr>
                            ) : (
                                templates.map((template, index) => (
                                    <tr key={template.id}>
                                        <td>
                                            {(pagination.current_page - 1) *
                                                pagination.per_page +
                                                index +
                                                1}
                                        </td>
                                        <td>{template.name}</td>
                                        <td>
                                            <code className="template-code">
                                                {template.code}
                                            </code>
                                        </td>
                                        <td>{template.channel_display}</td>
                                        <td>
                                            <span
                                                className={`badge ${
                                                    template.status === 'active'
                                                        ? 'badge-success'
                                                        : 'badge-warning'
                                                }`}
                                            >
                                                {template.status_display}
                                            </span>
                                        </td>
                                        <td>{template.updated_at_display}</td>
                                        <td>
                                            <div className="table-actions">
                                                <button
                                                    type="button"
                                                    className="table-action-button regenerate-button"
                                                    onClick={() =>
                                                        openEditModal(template)
                                                    }
                                                >
                                                    編輯
                                                </button>

                                                <button
                                                    type="button"
                                                    className="table-action-button delete-button"
                                                    onClick={() =>
                                                        setDeleteTarget(template)
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
                open={Boolean(formMode)}
                title={formMode === 'edit' ? '編輯模板' : '新增模板'}
                closeDisabled={saving}
                onClose={closeFormModal}
                actions={(
                    <>
                        <button
                            type="button"
                            className="secondary-button"
                            disabled={saving}
                            onClick={closeFormModal}
                        >
                            取消
                        </button>

                        <button
                            type="submit"
                            form="template-form"
                            className="primary-button"
                            disabled={saving}
                        >
                            {saving ? '儲存中...' : '儲存'}
                        </button>
                    </>
                )}
            >
                <form id="template-form" onSubmit={handleSave}>
                    <div className="form-grid">
                        <TemplateField
                            label="名稱"
                            name="name"
                            value={form.name}
                            error={formErrors.name?.[0]}
                            onChange={handleFormChange}
                            required
                            autoFocus
                        />

                        <TemplateField
                            label="代碼"
                            name="code"
                            value={form.code}
                            error={formErrors.code?.[0]}
                            onChange={handleFormChange}
                            required
                        />

                        <label className="form-field">
                            <span>通知管道</span>
                            <CustomSelect
                                value={form.channel}
                                ariaLabel="通知管道"
                                options={channelOptions}
                                onChange={(value) =>
                                    handleFormValueChange('channel', value)
                                }
                            />
                            {formErrors.channel && (
                                <small className="field-error">
                                    {formErrors.channel[0]}
                                </small>
                            )}
                        </label>

                        <label className="form-field">
                            <span>狀態</span>
                            <CustomSelect
                                value={form.status}
                                ariaLabel="模板狀態"
                                options={[
                                    { value: 'active', label: '啟用' },
                                    { value: 'inactive', label: '停用' },
                                ]}
                                onChange={(value) =>
                                    handleFormValueChange('status', value)
                                }
                            />
                            {formErrors.status && (
                                <small className="field-error">
                                    {formErrors.status[0]}
                                </small>
                            )}
                        </label>
                    </div>

                    <TemplateField
                        label="Email 主旨（選填）"
                        name="subject"
                        value={form.subject}
                        error={formErrors.subject?.[0]}
                        onChange={handleFormChange}
                    />

                    <label className="form-field">
                        <span>內容</span>
                        <textarea
                            name="content"
                            value={form.content}
                            rows="8"
                            required
                            onChange={handleFormChange}
                        />
                        {formErrors.content && (
                            <small className="field-error">
                                {formErrors.content[0]}
                            </small>
                        )}
                    </label>
                </form>
            </Modal>

            <Modal
                open={Boolean(deleteTarget)}
                title="確認刪除模板？"
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
                    刪除後此模板將無法再用於新的通知。
                </p>
            </Modal>
        </div>
    );
}

function TemplateField({
    label,
    name,
    value,
    error,
    onChange,
    ...inputProps
}) {
    return (
        <label className="form-field">
            <span>{label}</span>
            <input
                name={name}
                value={value}
                onChange={onChange}
                {...inputProps}
            />
            {error && (
                <small className="field-error">
                    {error}
                </small>
            )}
        </label>
    );
}
