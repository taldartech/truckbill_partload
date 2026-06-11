<template>
    <aside class="app-sidebar bg-white shadow-sm border-end">
        <div class="sidebar-brand py-4 px-3 d-flex justify-content-center border-bottom mb-2">
            <Link href="/" class="brand-link text-decoration-none">
                <img src="/logo.png" alt="TruckBill Logo" class="brand-image" style="height: 100px;">
            </Link>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-2">
                <ul class="nav sidebar-menu flex-column" role="menu">
                    <li class="nav-item">
                        <Link href="/" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url === '/' }">
                            <i class="nav-icon fas fa-th-large me-3"></i>
                            <p class="mb-0 fw-medium">Dashboard</p>
                        </Link>
                    </li>

                    <template v-if="showOperationsMenu">
                    <li class="nav-item" v-if="can('view bilties')">
                        <Link href="/bilties" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/bilties') }">
                            <i class="nav-icon fas fa-file-invoice me-3"></i>
                            <p class="mb-0 fw-medium">Bilty</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/loading-reports" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ active: $page.url.startsWith('/loading-reports') }">
                            <i class="nav-icon fas fa-truck-loading me-3"></i>
                            <p class="mb-0 fw-medium">Loading Reports</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view challans')">
                        <Link href="/challans" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/challans') }">
                            <i class="nav-icon fas fa-file-contract me-3"></i>
                            <p class="mb-0 fw-medium">Challan</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view challans')">
                        <Link href="/main-challans" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/main-challans') }">
                            <i class="nav-icon fas fa-file-alt me-3"></i>
                            <p class="mb-0 fw-medium">Main Challan</p>
                        </Link>
                    </li>

                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small">TRANSACTIONS</li>

                    <!-- <li class="nav-item">
                        <Link href="/vouchers" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ active: $page.url.startsWith('/vouchers') }">
                            <i class="nav-icon fas fa-file-invoice-dollar me-3"></i>
                            <p class="mb-0 fw-medium">Vouchers</p>
                        </Link>
                    </li> -->

                    <li class="nav-item">
                        <Link href="/inward-registers" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ active: $page.url.startsWith('/inward-registers') }">
                            <i class="nav-icon fas fa-clipboard-list me-3"></i>
                            <p class="mb-0 fw-medium">Inward Registers</p>
                        </Link>
                    </li>

                    <!-- <li class="nav-item">
                        <Link href="/delivery-receipts" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ active: $page.url.startsWith('/delivery-receipts') }">
                            <i class="nav-icon fas fa-receipt me-3"></i>
                            <p class="mb-0 fw-medium">Delivery Receipts</p>
                        </Link>
                    </li> -->

                    <!-- <li class="nav-item">
                        <Link href="/invoices" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ active: $page.url.startsWith('/invoices') }">
                            <i class="nav-icon fas fa-file-invoice-dollar me-3"></i>
                            <p class="mb-0 fw-medium">Tax Invoices</p>
                        </Link>
                    </li> -->

                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small">MASTER DATA</li>

                    <li class="nav-item" v-if="can('view vendors')">
                        <Link href="/vendors" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/vendors') }">
                            <i class="nav-icon fas fa-store me-3"></i>
                            <p class="mb-0 fw-medium">Vendors</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view transporters')">
                        <Link href="/transporters" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/transporters') }">
                            <i class="nav-icon fas fa-truck-loading me-3"></i>
                            <p class="mb-0 fw-medium">Transporter</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view drivers')">
                        <Link href="/drivers" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/drivers') }">
                            <i class="nav-icon fas fa-id-card me-3"></i>
                            <p class="mb-0 fw-medium">Drivers</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view stations')">
                        <Link href="/stations" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/stations') }">
                            <i class="nav-icon fas fa-map-marker-alt me-3"></i>
                            <p class="mb-0 fw-medium">Stations</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/groups" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/groups') }">
                            <i class="nav-icon fas fa-users me-3"></i>
                            <p class="mb-0 fw-medium">Groups</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/goods" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/goods') }">
                            <i class="nav-icon fas fa-box me-3"></i>
                            <p class="mb-0 fw-medium">Goods</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/package-types" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/package-types') }">
                            <i class="nav-icon fas fa-boxes me-3"></i>
                            <p class="mb-0 fw-medium">Package Types</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view trucks')">
                        <Link href="/trucks" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/trucks') }">
                            <i class="nav-icon fas fa-truck me-3"></i>
                            <p class="mb-0 fw-medium">Truck</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view parties')">
                        <Link href="/parties" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/parties') }">
                            <i class="nav-icon fas fa-user-friends me-3"></i>
                            <p class="mb-0 fw-medium">Consignee/Customer</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/truck-types" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/truck-types') }">
                            <i class="nav-icon fas fa-truck-moving me-3"></i>
                            <p class="mb-0 fw-medium">Truck Types</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/agents" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ active: $page.url.startsWith('/agents') }">
                            <i class="nav-icon fas fa-user-secret me-3"></i>
                            <p class="mb-0 fw-medium">Agents</p>
                        </Link>
                    </li>
                    <!-- <li class="nav-header text-uppercase text-muted fs-7 fw-bold mt-4 mb-2 px-3">Reports</li>

                    <li class="nav-item">
                        <Link href="/reports/bilty-register" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ active: $page.url.startsWith('/reports/bilty-register') }">
                            <i class="nav-icon fas fa-list-alt me-3"></i>
                            <p class="mb-0 fw-medium">Bilty Register</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/reports/challan-register" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ active: $page.url.startsWith('/reports/challan-register') }">
                            <i class="nav-icon fas fa-list me-3"></i>
                            <p class="mb-0 fw-medium">Challan Register</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/reports/party-ledger" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ active: $page.url.startsWith('/reports/party-ledger') }">
                            <i class="nav-icon fas fa-book me-3"></i>
                            <p class="mb-0 fw-medium">Party Ledger</p>
                        </Link>
                    </li> -->
                    </template>

                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small" v-if="showPortalMenu">PORTAL</li>
                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small" v-else-if="showSetupMenu">SETUP</li>
                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small" v-else-if="showOperationsMenu">ADMINISTRATION</li>

                    <template v-if="showPortalMenu">
                    <li class="nav-item" v-if="can('view users')">
                        <Link href="/users" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/users') }">
                            <i class="nav-icon fas fa-users-cog me-3"></i>
                            <p class="mb-0 fw-medium">Manage Super Admins</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view roles')">
                        <Link href="/roles" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/roles') }">
                            <i class="nav-icon fas fa-user-shield me-3"></i>
                            <p class="mb-0 fw-medium">Roles & Permissions</p>
                        </Link>
                    </li>

                    <!-- <li class="nav-item" v-if="can('view subscriptions')">
                        <Link href="/subscriptions" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/subscriptions') }">
                            <i class="nav-icon fas fa-credit-card me-3"></i>
                            <p class="mb-0 fw-medium">Subscriptions</p>
                        </Link>
                    </li> -->
                    </template>

                    <template v-if="showSetupMenu">
                    <li class="nav-item" v-if="can('view companies')">
                        <Link href="/companies" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/companies') }">
                            <i class="nav-icon fas fa-building me-3"></i>
                            <p class="mb-0 fw-medium">Manage Companies</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view branches')">
                        <Link href="/branches" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/branches') }">
                            <i class="nav-icon fas fa-code-branch me-3"></i>
                            <p class="mb-0 fw-medium">Manage Branches</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view users')">
                        <Link href="/users" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/users') }">
                            <i class="nav-icon fas fa-users-cog me-3"></i>
                            <p class="mb-0 fw-medium">Manage Users</p>
                        </Link>
                    </li>

                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small">SHARED MASTER DATA</li>

                    <li class="nav-item" v-if="can('view stations')">
                        <Link href="/stations" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/stations') }">
                            <i class="nav-icon fas fa-map-marker-alt me-3"></i>
                            <p class="mb-0 fw-medium">Stations</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view goods')">
                        <Link href="/goods" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/goods') }">
                            <i class="nav-icon fas fa-box me-3"></i>
                            <p class="mb-0 fw-medium">Goods</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view package_types')">
                        <Link href="/package-types" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/package-types') }">
                            <i class="nav-icon fas fa-boxes me-3"></i>
                            <p class="mb-0 fw-medium">Package Types</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view truck_types')">
                        <Link href="/truck-types" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/truck-types') }">
                            <i class="nav-icon fas fa-truck-moving me-3"></i>
                            <p class="mb-0 fw-medium">Truck Types</p>
                        </Link>
                    </li>

                    <!-- <li class="nav-item" v-if="can('view subscriptions')">
                        <Link href="/subscriptions" class="nav-link py-2 px-2 d-flex align-items-center" :class="{ 'active': $page.url.startsWith('/subscriptions') }">
                            <i class="nav-icon fas fa-credit-card me-3"></i>
                            <p class="mb-0 fw-medium">Subscriptions</p>
                        </Link>
                    </li> -->
                    </template>
                </ul>
            </nav>
        </div>
    </aside>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';

const { can, isSuperAdmin, isActingAsAdmin, showPortalMenu, showSetupMenu, showOperationsMenu } = usePermissions();
</script>

<style scoped>
.nav-link {
    color: var(--brand-text-muted);
    border-left: 4px solid transparent;
    transition: all 0.2s;
}
.nav-link:hover {
    background-color: var(--brand-table-header-bg);
    color: var(--brand-primary);
}
.nav-link.active {
    background-color: var(--brand-active-bg);
    color: var(--brand-primary) !important;
    border-left: 4px solid var(--brand-primary);
    font-weight: 600;
}
.nav-link.active i {
    color: var(--brand-primary);
}
.nav-header {
    background-color: var(--brand-table-header-bg);
    letter-spacing: 0.05em;
}

.sidebar-wrapper {
    height: calc(100vh - 110px); /* Adjust for sidebar-brand height */
    overflow-y: auto;
    scrollbar-width: thin;
}
.sidebar-wrapper::-webkit-scrollbar {
    width: 6px;
}
.sidebar-wrapper::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,0.2);
    border-radius: 4px;
}
</style>
