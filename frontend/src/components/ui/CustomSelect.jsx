import { useEffect, useId, useRef, useState } from 'react';

export default function CustomSelect({
    value,
    options,
    onChange,
    ariaLabel,
    className = '',
    disabled = false,
}) {
    const rootRef = useRef(null);
    const listboxId = useId();
    const selectedIndex = Math.max(
        options.findIndex((option) => option.value === value),
        0
    );

    const [open, setOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(selectedIndex);

    useEffect(() => {
        setActiveIndex(selectedIndex);
    }, [selectedIndex]);

    useEffect(() => {
        function handleOutsidePointer(event) {
            if (!rootRef.current?.contains(event.target)) {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', handleOutsidePointer);

        return () => {
            document.removeEventListener('mousedown', handleOutsidePointer);
        };
    }, []);

    function selectOption(index) {
        const option = options[index];

        if (!option || option.disabled) {
            return;
        }

        onChange(option.value);
        setActiveIndex(index);
        setOpen(false);
    }

    function moveActiveIndex(direction) {
        let nextIndex = activeIndex;

        do {
            nextIndex =
                (nextIndex + direction + options.length) %
                options.length;
        } while (options[nextIndex]?.disabled && nextIndex !== activeIndex);

        setActiveIndex(nextIndex);
    }

    function handleKeyDown(event) {
        if (disabled) {
            return;
        }

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                if (!open) {
                    setOpen(true);
                    setActiveIndex(selectedIndex);
                } else {
                    moveActiveIndex(1);
                }
                break;
            case 'ArrowUp':
                event.preventDefault();
                if (!open) {
                    setOpen(true);
                    setActiveIndex(selectedIndex);
                } else {
                    moveActiveIndex(-1);
                }
                break;
            case 'Enter':
            case ' ':
                event.preventDefault();
                if (open) {
                    selectOption(activeIndex);
                } else {
                    setOpen(true);
                    setActiveIndex(selectedIndex);
                }
                break;
            case 'Escape':
                event.preventDefault();
                setOpen(false);
                break;
            case 'Home':
                if (open) {
                    event.preventDefault();
                    setActiveIndex(0);
                }
                break;
            case 'End':
                if (open) {
                    event.preventDefault();
                    setActiveIndex(options.length - 1);
                }
                break;
            case 'Tab':
                setOpen(false);
                break;
            default:
                break;
        }
    }

    const selectedOption = options[selectedIndex];

    return (
        <div
            ref={rootRef}
            className={`custom-select ${className}`.trim()}
        >
            <button
                type="button"
                className="custom-select-trigger"
                aria-label={ariaLabel}
                aria-haspopup="listbox"
                aria-expanded={open}
                aria-controls={listboxId}
                aria-activedescendant={
                    open
                        ? `${listboxId}-option-${activeIndex}`
                        : undefined
                }
                disabled={disabled}
                onClick={() => {
                    setOpen((current) => !current);
                    setActiveIndex(selectedIndex);
                }}
                onKeyDown={handleKeyDown}
            >
                <span>{selectedOption?.label}</span>
                <span className="custom-select-arrow" aria-hidden="true" />
            </button>

            {open && (
                <div
                    id={listboxId}
                    className="custom-select-menu"
                    role="listbox"
                    aria-label={ariaLabel}
                >
                    {options.map((option, index) => (
                        <button
                            id={`${listboxId}-option-${index}`}
                            key={option.value}
                            type="button"
                            role="option"
                            aria-selected={option.value === value}
                            className={`custom-select-option ${
                                index === activeIndex ? 'active' : ''
                            } ${
                                option.value === value ? 'selected' : ''
                            }`.trim()}
                            disabled={option.disabled}
                            onMouseEnter={() => setActiveIndex(index)}
                            onClick={() => selectOption(index)}
                        >
                            <span>{option.label}</span>
                            {option.value === value && (
                                <span aria-hidden="true">✓</span>
                            )}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
