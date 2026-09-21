const DEFAULT_ADD_MORE_MESSAGE = 'Do you want to add more details?';

const normalizeMessage = (value) => {
    const message = value?.trim();
    return message || null;
};

const messageFromAddButtonText = (addBtn) => {
    if (!addBtn) return null;

    const text = addBtn.textContent.replace(/\s+/g, ' ').trim();
    const match = text.match(/^add\s+(.+)$/i);
    if (!match) return null;

    return `Do you want to add another ${match[1].toLowerCase()}?`;
};

const messageFromSectionLabel = (container) => {
    const label = container?.querySelector('label')?.textContent?.trim();
    if (!label) return null;

    const lower = label.toLowerCase();
    if (lower === 'e-way bills') return 'Do you want to add another e-way bill?';
    if (lower === 'addon charges') return 'Do you want to add another addon charge?';

    const singular = lower.endsWith('ies')
        ? `${lower.slice(0, -3)}y`
        : lower.endsWith('s')
            ? lower.slice(0, -1)
            : lower;

    return `Do you want to add another ${singular}?`;
};

export const resolveAddMoreMessage = (container, addBtn, activeEl) => {
    return normalizeMessage(activeEl?.getAttribute('data-dynamic-add-message'))
        || normalizeMessage(container?.getAttribute('data-dynamic-add-message'))
        || normalizeMessage(addBtn?.getAttribute('data-dynamic-add-message'))
        || messageFromAddButtonText(addBtn)
        || messageFromSectionLabel(container)
        || DEFAULT_ADD_MORE_MESSAGE;
};

export const getVisibleRowInputs = (row) => {
    return Array.from(
        row.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled])')
    ).filter((el) => {
        const style = window.getComputedStyle(el);
        return style.display !== 'none' && style.visibility !== 'hidden' && el.offsetWidth > 0 && el.offsetHeight > 0;
    });
};

export const getRowsWithInputs = (container) => {
    return Array.from(container.querySelectorAll('tr, .dynamic-row')).filter((row) => getVisibleRowInputs(row).length > 0);
};

export const findAddRowButton = (container, contextRow = null) => {
    if (contextRow) {
        const rowType = contextRow.getAttribute('data-detail-row-type');
        if (rowType) {
            const typedBtn = container.querySelector(`[data-dynamic-add][data-dynamic-add-for="${rowType}"]`);
            if (typedBtn) return typedBtn;
        }
    }

    const explicit = container.querySelector('[data-dynamic-add]') || container.closest('section').querySelector('[data-dynamic-add]');
    if (explicit) return explicit;

    return Array.from(container.querySelectorAll('button, a')).find((btn) => {
        const text = btn.textContent.toLowerCase().trim();
        return text.includes('add row')
            || text.includes('add bilty')
            || text.includes('add inward')
            || text.includes('add e-way')
            || text.includes('add eway')
            || text.includes('add addon')
            || text.includes('add charge')
            || text === 'add';
    }) || null;
};
