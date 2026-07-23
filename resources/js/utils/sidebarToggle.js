const SIDEBAR_BREAKPOINT = 992;
const OVERLAY_CLASS = 'app-sidebar-backdrop';

const ensureSidebarOverlay = () => {
    let overlay = document.querySelector(`.${OVERLAY_CLASS}`);

    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = OVERLAY_CLASS;
        overlay.setAttribute('aria-hidden', 'true');
        overlay.addEventListener('click', (event) => {
            event.preventDefault();
            collapseSidebar();
        });
        document.body.appendChild(overlay);
    }

    return overlay;
};

export const expandSidebar = () => {
    document.body.classList.remove('sidebar-collapse');
    document.body.classList.add('sidebar-open');
    ensureSidebarOverlay();
};

export const collapseSidebar = () => {
    document.body.classList.remove('sidebar-open');
    document.body.classList.add('sidebar-collapse');
};

export const toggleSidebar = () => {
    if (document.body.classList.contains('sidebar-collapse')) {
        expandSidebar();
        return;
    }

    collapseSidebar();
};

export const syncSidebarForViewport = () => {
    ensureSidebarOverlay();

    if (window.innerWidth <= SIDEBAR_BREAKPOINT) {
        collapseSidebar();
        return;
    }

    if (!document.body.classList.contains('sidebar-mini')) {
        expandSidebar();
    }
};
