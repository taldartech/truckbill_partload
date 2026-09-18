<template>
    <nav class="app-header navbar navbar-expand bg-white border-bottom shadow-sm">
        <div class="container-fluid app-header-inner">
            <ul class="navbar-nav app-header-start align-items-center">
                <li class="nav-item">
                    <button
                        type="button"
                        class="btn btn-link text-muted p-1 p-sm-2 app-header-icon-btn w-sm-auto"
                        aria-label="Toggle sidebar"
                        @click="toggleSidebar"
                    >
                        <i class="fas fa-bars"></i>
                    </button>
                </li>
                <li class="nav-item d-none d-md-block ms-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-building text-muted me-2"></i>
                            <span class="fw-semibold small text-secondary">{{ $page.props.auth?.company_name || 'Demo Company' }}</span>
                        </div>
                        <div v-if="$page.props.auth?.branch_name" class="d-flex align-items-center">
                            <i class="fas fa-code-branch text-muted me-2"></i>
                            <span class="fw-semibold small text-secondary">{{ $page.props.auth.branch_name }}</span>
                        </div>
                    </div>
                </li>
            </ul>

            <ul class="navbar-nav app-header-end ms-auto align-items-center">
                <li class="keyboard-btn d-none d-md-block">
                    <button type="button" class=" btn btn-primary btn-sm d-flex align-items-center gap-2 me-2"
                          @click="$emit('quick-action')">
                        <span><i class="fa-solid fa-bolt-lightning text-warning"></i> &nbsp; Shortcut</span>
                        <span class="badge top-0 d-inline-flex align-items-center justify-content-center"  style="background-color: var(--brand-accent-blue)">
                            F1
                        </span>
                    </button>
                </li>
                <li v-if="$page.props.auth?.is_super_admin" class="nav-item me-2">
                    <Link
                        v-if="$page.props.auth?.is_acting_as_admin"
                        href="/session/branch"
                        method="delete"
                        as="button"
                        class="btn btn-sm btn-outline-secondary"
                    >
                        <i class="fas fa-cog "></i><span class="ms-1 hide-text">Setup Mode</span>
                    </Link>
                    <Link
                        v-else
                        href="/select-branch"
                        class="btn btn-sm btn-primary"
                    >
                        <i class="fas fa-code-branch "></i><span class="ms-1 hide-text">Select Branch</span>
                    </Link>
                </li>

                <!-- <li class="nav-item d-none d-lg-block me-2">
                    <div class="badge rounded-pill bg-light border text-dark px-3 py-2 d-flex align-items-center small shadow-sm">
                        <i class="far fa-calendar-alt text-success me-2"></i>
                        <span class="text-muted me-1">EXPIRY:</span>
                        <span class="fw-bold" :class="$page.props.auth?.subscription_ends_at ? 'text-success' : 'text-danger'">
                            {{ $page.props.auth?.subscription_ends_at || 'Not Subscribed' }}
                        </span>
                    </div>
                </li> -->

                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center py-1 px-2 rounded-2 hover-bg" data-bs-toggle="dropdown">
                        <div class="bg-secondary bg-opacity-10 rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                            <i class="fas fa-user text-muted small"></i>
                        </div>
                        <span class="fw-semibold small text-secondary">{{ $page.props.auth?.user?.name || 'User' }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2" style="min-width: 200px;">
                        <!-- <a href="#" class="dropdown-item py-2">
                            <i class="fas fa-user-circle me-2 text-muted"></i> Profile
                        </a> -->
                        <Link href="/password" class="dropdown-item py-2">
                            <i class="fas fa-key me-2 text-muted"></i> Change Password
                        </Link>
                        <div class="dropdown-divider"></div>
                        <Link href="/logout" method="post" as="button" class="dropdown-item py-2 text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Sign out
                        </Link>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { collapseSidebar, syncSidebarForViewport, toggleSidebar } from '@/utils/sidebarToggle';

const SIDEBAR_BREAKPOINT = 992;
let removeNavigateListener = null;

onMounted(() => {
    if (
        !document.body.classList.contains('sidebar-open')
        && !document.body.classList.contains('sidebar-collapse')
    ) {
        syncSidebarForViewport();
    }

    window.addEventListener('resize', syncSidebarForViewport);

    removeNavigateListener = router.on('navigate', () => {
        if (window.innerWidth <= SIDEBAR_BREAKPOINT) {
            collapseSidebar();
        }
    });
});

onUnmounted(() => {
    window.removeEventListener('resize', syncSidebarForViewport);
    removeNavigateListener?.();
});
</script>

<style scoped>
.keyboard-btn {
    display: none;
}
@media (pointer: fine) {
    .keyboard-btn {
      display: block; 
    }
}
@media (max-width: 360px) {
    .hide-text {
        display: none !important;
    }
}
.hover-bg:hover {
    background-color: #f3f4f6;
}
.dropdown-item:hover {
    background-color: #f9fafb;
}
</style>
