<template>
    <nav class="app-header navbar navbar-expand bg-white border-bottom shadow-sm py-2">
        <div class="container-fluid">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item">
                    <button class="btn btn-link text-muted p-2" data-lte-toggle="sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                </li>
                <li class="nav-item d-none d-md-block ms-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-building text-muted me-2"></i>
                        <span class="fw-bold text-dark small">Demo Company</span>
                    </div>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                <!-- Info Pills -->
                <li class="nav-item d-none d-lg-block me-2">
                    <div class="badge rounded-pill bg-light border text-dark px-3 py-2 d-flex align-items-center small shadow-sm">
                        <i class="far fa-calendar-alt text-success me-2"></i>
                        <span class="text-muted me-1">EXPIRY:</span>
                        <span class="fw-bold text-success">25 Mar, 2027</span>
                    </div>
                </li>
                <li class="nav-item d-none d-lg-block me-3">
                    <div class="badge rounded-pill bg-light border text-dark px-3 py-2 d-flex align-items-center small shadow-sm">
                        <i class="fas fa-coins text-success me-2"></i>
                        <span class="text-muted me-1">CREDITS:</span>
                        <span class="fw-bold text-success">680</span>
                    </div>
                </li>

                <!-- User Menu -->
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center py-1 px-2 rounded-2 hover-bg" data-bs-toggle="dropdown">
                        <div class="bg-secondary bg-opacity-10 rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                            <i class="fas fa-user text-muted small"></i>
                        </div>
                        <span class="fw-bold text-dark small">Admin</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2" style="min-width: 200px;">
                        <a href="#" class="dropdown-item py-2">
                            <i class="fas fa-user-circle me-2 text-muted"></i> Profile
                        </a>
                        <a href="#" class="dropdown-item py-2">
                            <i class="fas fa-key me-2 text-muted"></i> Change Password
                        </a>
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
import { Link } from '@inertiajs/vue3';
</script>

<style scoped>
.hover-bg:hover {
    background-color: #f3f4f6;
}
.dropdown-item:hover {
    background-color: #f9fafb;
}
</style>
