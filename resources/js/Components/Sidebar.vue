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

                    <li class="nav-item" v-if="can('view loading_reports')">
                        <Link href="/loading-reports" class="nav-link" :class="{ active: $page.url.startsWith('/loading-reports') }">
                            <i class="nav-icon fas fa-truck-loading"></i>
                            <p>Loading Report</p>
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

                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small">TRANSACTIONS</li>

                    <li class="nav-item" v-if="can('view inward_registers')">
                        <Link href="/inward-registers" class="nav-link" :class="{ active: $page.url.startsWith('/inward-registers') }">
                            <i class="nav-icon fas fa-clipboard-list"></i>
                            <p>Inward Registers</p>
                        </Link>
                    </li>

                    <li class="nav-item"  v-if="can('view delivery_receipts')">
                        <Link href="/delivery-receipts" class="nav-link" :class="{ active: $page.url.startsWith('/delivery-receipts') }">
                            <i class="nav-icon fas fa-receipt"></i>
                            <p>Delivery Receipts</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view invoices')">
                        <Link href="/invoices" class="nav-link" :class="{ active: $page.url.startsWith('/invoices') }">
                            <i class="nav-icon fas fa-file-invoice-dollar"></i>
                            <p>Invoices</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view accounting_vouchers')">
                        <Link href="/accounting/vouchers" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/vouchers') }">
                            <i class="nav-icon fas fa-book"></i>
                            <p>Accounting Vouchers</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view bank_accounts')">
                        <Link href="/bank-accounts" class="nav-link" :class="{ active: $page.url.startsWith('/bank-accounts') }">
                            <i class="nav-icon fas fa-university"></i>
                            <p>Bank Accounts</p>
                        </Link>
                    </li>

                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small" v-if="can('view accounts') || can('view account_groups') || can('create accounting_settings') || can('view accounting_settings')">ACCOUNTING</li>

                    <li class="nav-item" v-if="can('view accounts')">
                        <Link href="/accounting/accounts" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/accounts') }">
                            <i class="nav-icon fas fa-sitemap"></i>
                            <p>Chart of Accounts</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view account_groups')">
                        <Link href="/accounting/account-groups" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/account-groups') }">
                            <i class="nav-icon fas fa-layer-group"></i>
                            <p>Account Groups</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('create accounting_settings')">
                        <Link href="/accounting/opening-balances/create" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/opening-balances') }">
                            <i class="nav-icon fas fa-balance-scale"></i>
                            <p>Opening Balances</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view accounting_settings')">
                        <Link href="/accounting/financial-years" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/financial-years') }">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <p>Financial Years</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view accounting_settings')">
                        <Link href="/accounting/health" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/health') }">
                            <i class="nav-icon fas fa-heartbeat"></i>
                            <p>Accounting Health</p>
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

                    <li class="nav-item" v-if="can('view goods')">
                        <Link href="/goods" class="nav-link" :class="{ active: $page.url.startsWith('/goods') }">
                            <i class="nav-icon fas fa-box"></i>
                            <p>Goods</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view groups')">
                        <Link href="/groups" class="nav-link" :class="{ active: $page.url.startsWith('/groups') }">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Groups</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view package_types')">
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

                    <li class="nav-item" v-if="can('view truck_types')">
                        <Link href="/truck-types" class="nav-link" :class="{ active: $page.url.startsWith('/truck-types') }">
                            <i class="nav-icon fas fa-truck-moving"></i>
                            <p>Truck Types</p>
                        </Link>
                    </li>

                    <li class="nav-item" v-if="can('view agents')">
                        <Link href="/agents" class="nav-link" :class="{ active: $page.url.startsWith('/agents') }">
                            <i class="nav-icon fas fa-user-secret"></i>
                            <p>Agents</p>
                        </Link>
                    </li>

                    <li class="nav-header mt-2 px-2 py-2 text-muted fw-bold small">REPORTS</li>

                    <li class="nav-item" v-if="can('view accounting_reports')">
                        <Link href="/accounting/reports/ledger" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/reports/ledger') }">
                            <i class="nav-icon fas fa-book"></i>
                            <p>Account Ledger</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view accounting_reports')">
                        <Link href="/accounting/reports/day-book" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/reports/day-book') }">
                            <i class="nav-icon fas fa-calendar-day"></i>
                            <p>Day Book</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view accounting_reports')">
                        <Link href="/accounting/reports/cash-book" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/reports/cash-book') }">
                            <i class="nav-icon fas fa-wallet"></i>
                            <p>Cash Book</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view accounting_reports')">
                        <Link href="/accounting/reports/bank-book" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/reports/bank-book') }">
                            <i class="nav-icon fas fa-university"></i>
                            <p>Bank Book</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view accounting_reports')">
                        <Link href="/accounting/reports/trial-balance" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/reports/trial-balance') }">
                            <i class="nav-icon fas fa-list-ol"></i>
                            <p>Trial Balance</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view accounting_reports')">
                        <Link href="/accounting/reports/profit-and-loss" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/reports/profit-and-loss') }">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>Profit &amp; Loss</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view accounting_reports')">
                        <Link href="/accounting/reports/balance-sheet" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/reports/balance-sheet') }">
                            <i class="nav-icon fas fa-file-invoice"></i>
                            <p>Balance Sheet</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view accounting_reports')">
                        <Link href="/accounting/reports/receivables" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/reports/receivables') }">
                            <i class="nav-icon fas fa-hand-holding-usd"></i>
                            <p>Receivables</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view accounting_reports')">
                        <Link href="/accounting/reports/payables" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/reports/payables') }">
                            <i class="nav-icon fas fa-file-invoice-dollar"></i>
                            <p>Payables</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view accounting_reports')">
                        <Link href="/accounting/reports/party-ledger" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/reports/party-ledger') }">
                            <i class="nav-icon fas fa-address-book"></i>
                            <p>Party Ledger</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view accounting_reports')">
                        <Link href="/accounting/reports/voucher-register" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/reports/voucher-register') }">
                            <i class="nav-icon fas fa-list-alt"></i>
                            <p>Voucher Register</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view accounting_reports')">
                        <Link href="/accounting/reports/expenses" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/reports/expenses') }">
                            <i class="nav-icon fas fa-receipt"></i>
                            <p>Expense Report</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view accounting_reports')">
                        <Link href="/accounting/reports/gst" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/reports/gst') }">
                            <i class="nav-icon fas fa-percent"></i>
                            <p>GST Report</p>
                        </Link>
                    </li>
                    <li class="nav-item" v-if="can('view accounting_reports')">
                        <Link href="/accounting/reports/tds" class="nav-link" :class="{ active: $page.url.startsWith('/accounting/reports/tds') }">
                            <i class="nav-icon fas fa-file-contract"></i>
                            <p>TDS Report</p>
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

                    <li class="nav-item" >
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
