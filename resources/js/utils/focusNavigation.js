export const getFocusableElements = (root = document.body) => {
    return Array.from(
        root.querySelectorAll(
            'input:not([type="hidden"]):not([disabled]):not([type="button"]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]):not([type="button"]), [tabindex="0"]'
        )
    ).filter((el) => {
        if (el.tabIndex < 0 || el.getAttribute('tabindex') === '-1') return false;
        const style = window.getComputedStyle(el);
        return style.display !== 'none' && style.visibility !== 'hidden' && el.offsetWidth > 0 && el.offsetHeight > 0;
    });
};

export const focusNextFocusable = (fromEl, skipContainer = null) => {
    if (!(fromEl instanceof HTMLElement)) return false;

    const form = fromEl.closest('form') || document.body;
    const allFocusable = getFocusableElements(form);
    const fromIndex = allFocusable.indexOf(fromEl);

    if (fromIndex === -1) return false;

    const nextElement = skipContainer
        ? allFocusable.slice(fromIndex + 1).find((el) => !skipContainer.contains(el))
        : allFocusable[fromIndex + 1];

    if (nextElement) {
        nextElement.focus();
        return true;
    }

    return false;
};
