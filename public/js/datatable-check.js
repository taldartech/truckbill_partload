/**
 * Helper function to safely check if DataTable is initialized
 * @param {string} selector - jQuery selector for the table
 * @returns {boolean} - Whether DataTable is initialized on the element
 */
function isDataTableInitialized(selector) {
    try {
        return $.fn.DataTable.isDataTable(selector);
    } catch (e) {
        console.log('DataTable check error:', e);
        return false;
    }
}

/**
 * Helper function to safely get DataTable instance
 * @param {string} selector - jQuery selector for the table
 * @returns {object|null} - DataTable instance or null if not initialized
 */
function safeGetDataTable(selector) {
    if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') {
        console.log('DataTable not available');
        return null;
    }
    
    try {
        if ($.fn.DataTable.isDataTable(selector)) {
            return $(selector).DataTable();
        } else {
            console.log('DataTable not initialized on', selector);
            return null;
        }
    } catch (e) {
        console.log('Error getting DataTable:', e);
        return null;
    }
}
