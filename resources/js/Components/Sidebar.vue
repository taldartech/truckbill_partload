<template>
    <aside class="app-sidebar bg-white shadow-sm border-end">
        <div class="sidebar-brand">
            <Link href="/" class="brand-link text-decoration-none">
                <img src="/logo.png" alt="TruckBill Logo" class="brand-image">
            </Link>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-2">
                <ul class="nav sidebar-menu flex-column" role="menu">
                    <li class="nav-item">
                        <Link href="/" class="nav-link" :class="{ active: $page.url === '/' }">
                            <i class="nav-icon fas fa-th-large"></i>
                            <p>Dashboard</p>
                        </Link>
                    </li>

                    <template v-if="showOperationsMenu">
                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small">OPERATIONS</li>

                    <li class="nav-item" v-if="can('view bilties')">
                        <Link href="/bilties" class="nav-link" :class="{ active: $page.url.startsWith('/bilties') }">
                            <i class="nav-icon fas fa-file-invoice"></i>
                            <p>Bilty</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view challans')">
                        <Link href="/challans" class="nav-link" :class="{ active: $page.url.startsWith('/challans') }">
                            <i class="nav-icon fas fa-file-contract"></i>
                            <p>Challans</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view challans')">
                        <Link href="/main-challans" class="nav-link" :class="{ active: $page.url.startsWith('/main-challans') }">
                            <i class="nav-icon fas fa-file-alt"></i>
                            <p>Main Challans</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/loading-reports" class="nav-link" :class="{ active: $page.url.startsWith('/loading-reports') }">
                            <i class="nav-icon fas fa-truck-loading"></i>
                            <p>Loading Reports</p>
                        </Link>
                    </li>

                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small">TRANSACTIONS</li>

                    <li class="nav-item">
                        <Link href="/inward-registers" class="nav-link" :class="{ active: $page.url.startsWith('/inward-registers') }">
                            <i class="nav-icon fas fa-clipboard-list"></i>
                            <p>Inward Registers</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/delivery-receipts" class="nav-link" :class="{ active: $page.url.startsWith('/delivery-receipts') }">
                            <i class="nav-icon fas fa-receipt"></i>
                            <p>Delivery Receipts</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/invoices" class="nav-link" :class="{ active: $page.url.startsWith('/invoices') }">
                            <i class="nav-icon fas fa-file-invoice-dollar"></i>
                            <p>Invoices</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/vouchers" class="nav-link" :class="{ active: $page.url.startsWith('/vouchers') }">
                            <i class="nav-icon fas fa-money-check-alt"></i>
                            <p>Vouchers</p>
                        </Link>
                    </li>

                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small">MASTER DATA</li>

                    <li class="nav-item" v-if="can('view parties')">
                        <Link href="/parties" class="nav-link" :class="{ active: $page.url.startsWith('/parties') }">
                            <i class="nav-icon fas fa-user-friends"></i>
                            <p>Parties</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view stations')">
                        <Link href="/stations" class="nav-link" :class="{ active: $page.url.startsWith('/stations') }">
                            <i class="nav-icon fas fa-map-marker-alt"></i>
                            <p>Stations</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/goods" class="nav-link" :class="{ active: $page.url.startsWith('/goods') }">
                            <i class="nav-icon fas fa-box"></i>
                            <p>Goods</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/groups" class="nav-link" :class="{ active: $page.url.startsWith('/groups') }">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Groups</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/package-types" class="nav-link" :class="{ active: $page.url.startsWith('/package-types') }">
                            <i class="nav-icon fas fa-boxes"></i>
                            <p>Package Types</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view trucks')">
                        <Link href="/trucks" class="nav-link" :class="{ active: $page.url.startsWith('/trucks') }">
                            <i class="nav-icon fas fa-truck"></i>
                            <p>Trucks</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/truck-types" class="nav-link" :class="{ active: $page.url.startsWith('/truck-types') }">
                            <i class="nav-icon fas fa-truck-moving"></i>
                            <p>Truck Types</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/agents" class="nav-link" :class="{ active: $page.url.startsWith('/agents') }">
                            <i class="nav-icon fas fa-user-secret"></i>
                            <p>Agents</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view vendors')">
                        <Link href="/vendors" class="nav-link" :class="{ active: $page.url.startsWith('/vendors') }">
                            <i class="nav-icon fas fa-store"></i>
                            <p>Vendors</p>
                        </Link>
                    </li>

                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small">REPORTS</li>

                    <li class="nav-item">
                        <Link href="/reports/bilty-register" class="nav-link" :class="{ active: $page.url.startsWith('/reports/bilty-register') }">
                            <i class="nav-icon fas fa-list-alt"></i>
                            <p>Bilty Register</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/reports/challan-register" class="nav-link" :class="{ active: $page.url.startsWith('/reports/challan-register') }">
                            <i class="nav-icon fas fa-list"></i>
                            <p>Challan Register</p>
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link href="/reports/party-ledger" class="nav-link" :class="{ active: $page.url.startsWith('/reports/party-ledger') }">
                            <i class="nav-icon fas fa-book"></i>
                            <p>Party Ledger</p>
                        </Link>
                    </li>
                    </template>

                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small" v-if="showPortalMenu">PORTAL</li>
                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small" v-else-if="showSetupMenu">SETUP</li>

                    <template v-if="showPortalMenu">
                    <li class="nav-item" v-if="can('view users')">
                        <Link href="/users" class="nav-link" :class="{ active: $page.url.startsWith('/users') }">
                            <i class="nav-icon fas fa-users-cog"></i>
                            <p>Super Admins</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view roles')">
                        <Link href="/roles" class="nav-link" :class="{ active: $page.url.startsWith('/roles') }">
                            <i class="nav-icon fas fa-user-shield"></i>
                            <p>Roles & Permissions</p>
                        </Link>
                    </li>
                    </template>

                    <template v-if="showSetupMenu">
                    <li class="nav-item" v-if="can('view companies')">
                        <Link href="/companies" class="nav-link" :class="{ active: $page.url.startsWith('/companies') }">
                            <i class="nav-icon fas fa-building"></i>
                            <p>Companies</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view branches')">
                        <Link href="/branches" class="nav-link" :class="{ active: $page.url.startsWith('/branches') }">
                            <i class="nav-icon fas fa-code-branch"></i>
                            <p>Branches</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view users')">
                        <Link href="/users" class="nav-link" :class="{ active: $page.url.startsWith('/users') }">
                            <i class="nav-icon fas fa-users-cog"></i>
                            <p>Users</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="isSuperAdmin">
                        <Link href="/subscription" class="nav-link" :class="{ active: $page.url.startsWith('/subscription') }">
                            <i class="nav-icon fas fa-credit-card"></i>
                            <p>Subscription</p>
                        </Link>
                    </li>

                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small">SHARED MASTER DATA</li>

                    <li class="nav-item" v-if="can('view stations')">
                        <Link href="/stations" class="nav-link" :class="{ active: $page.url.startsWith('/stations') }">
                            <i class="nav-icon fas fa-map-marker-alt"></i>
                            <p>Stations</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view goods')">
                        <Link href="/goods" class="nav-link" :class="{ active: $page.url.startsWith('/goods') }">
                            <i class="nav-icon fas fa-box"></i>
                            <p>Goods</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view package_types')">
                        <Link href="/package-types" class="nav-link" :class="{ active: $page.url.startsWith('/package-types') }">
                            <i class="nav-icon fas fa-boxes"></i>
                            <p>Package Types</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view truck_types')">
                        <Link href="/truck-types" class="nav-link" :class="{ active: $page.url.startsWith('/truck-types') }">
                            <i class="nav-icon fas fa-truck-moving"></i>
                            <p>Truck Types</p>
                        </Link>
                    </li>
                    </template>
                </ul>
            </nav>
        </div>
    </aside>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';

const { can, isSuperAdmin, showPortalMenu, showSetupMenu, showOperationsMenu } = usePermissions();
</script>
