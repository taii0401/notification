export default function Pagination({ meta, onPageChange }) {
    if (!meta || meta.total === 0) {
        return null;
    }

    return (
        <div className="list-pagination">
            <span className="pagination-summary">
                共 {meta.total} 筆
                {' · '}
                第 {meta.current_page}/{meta.last_page} 頁
            </span>

            <button
                type="button"
                className="pagination-link"
                disabled={meta.current_page <= 1}
                onClick={() => onPageChange(meta.current_page - 1)}
            >
                上一頁
            </button>

            <button
                type="button"
                className="pagination-link"
                disabled={meta.current_page >= meta.last_page}
                onClick={() => onPageChange(meta.current_page + 1)}
            >
                下一頁
            </button>
        </div>
    );
}
