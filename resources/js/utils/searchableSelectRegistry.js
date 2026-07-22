/**
 * Ensures only one SearchableSelect dropdown is open at a time,
 * and allows other UI (e.g. F3 quick-add) to close all dropdowns.
 */

let openController = null;

export function claimSearchableSelect(controller) {
    if (openController && openController !== controller) {
        openController.closeFromOutside();
    }

    openController = controller;
}

export function releaseSearchableSelect(controller) {
    if (openController === controller) {
        openController = null;
    }
}

export function closeAllSearchableSelects() {
    if (!openController) {
        return;
    }

    const current = openController;
    openController = null;
    current.closeFromOutside();
}
