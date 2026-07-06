export function openPrintUrl(url) {
    if (url) {
        window.open(url, '_blank');
    }
}

export function withPrintAfterSave(printAfterSave, printUrl = null) {
    if (!printAfterSave) {
        return {};
    }

    return {
        onSuccess: (page) => {
            openPrintUrl(printUrl || page.props.flash?.print_url);
        },
    };
}

export function withPrintFlag(data, printAfterSave) {
    return {
        ...data,
        print_after_save: printAfterSave,
    };
}
