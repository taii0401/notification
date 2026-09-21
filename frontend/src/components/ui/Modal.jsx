export default function Modal({
    open,
    title,
    children,
    actions,
    onClose,
    closeDisabled = false,
}) {
    if (!open) {
        return null;
    }

    function handleBackdropMouseDown(event) {
        if (
            event.target === event.currentTarget &&
            !closeDisabled
        ) {
            onClose();
        }
    }

    return (
        <div
            className="modal-backdrop"
            role="presentation"
            onMouseDown={handleBackdropMouseDown}
        >
            <section
                className="modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="modal-title"
            >
                <div className="modal-header">
                    <h2 id="modal-title">
                        {title}
                    </h2>
                </div>

                <div className="modal-body">
                    {children}
                </div>

                {actions && (
                    <div className="modal-actions">
                        {actions}
                    </div>
                )}
            </section>
        </div>
    );
}