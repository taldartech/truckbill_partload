<template>
    <AdminLayout title="Dashboard">
        <div class="dashboard-page">
            <div class="dashboard-welcome mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <!-- <span class="dashboard-welcome__badge mb-2 d-inline-block">{{ dashboard.welcome.role_label }}</span> -->
                        <h2 class="h4 mb-1 fw-bold">Welcome back, {{ dashboard.welcome.name }}</h2>
                        <p class="mb-2 opacity-90">{{ dashboard.welcome.subtitle }}</p>
                        <div class="dashboard-welcome__meta d-flex flex-wrap gap-3">
                            <span v-if="dashboard.welcome.company">
                                <i class="fas fa-building me-1"></i>{{ dashboard.welcome.company }}
                            </span>
                            <span v-if="dashboard.welcome.branch">
                                <i class="fas fa-code-branch me-1"></i>{{ dashboard.welcome.branch }}
                            </span>
                            <span><i class="fas fa-calendar-alt me-1"></i>{{ dashboard.welcome.date }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Master Admin -->
            <template v-if="dashboard.mode === 'master_admin'">
                <div class="dash-stat-grid mb-4">
                    <div class="dash-stat-card dash-stat-card--primary">
                        <div class="dash-stat-card__icon"><i class="fas fa-user-shield"></i></div>
                        <div>
                            <div class="dash-stat-card__value">{{ dashboard.stats.super_admins }}</div>
                            <div class="dash-stat-card__label">Super Admins</div>
                            <Link href="/users" class="dash-stat-card__link">Manage <i class="fas fa-arrow-right"></i></Link>
                        </div>
                    </div>
                    <div class="dash-stat-card dash-stat-card--info">
                        <div class="dash-stat-card__icon"><i class="fas fa-building"></i></div>
                        <div>
                            <div class="dash-stat-card__value">{{ dashboard.stats.companies }}</div>
                            <div class="dash-stat-card__label">Registered Companies</div>
                        </div>
                    </div>
                    <div class="dash-stat-card dash-stat-card--success">
                        <div class="dash-stat-card__icon"><i class="fas fa-credit-card"></i></div>
                        <div>
                            <div class="dash-stat-card__value">{{ dashboard.stats.active_subscriptions }}</div>
                            <div class="dash-stat-card__label">Active Subscriptions</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Portal Administration</h3>
                    </div>
                    <div class="card-body">
                        <div class="dash-quick-grid">
                            <Link
                                v-for="action in portalActions"
                                :key="action.href"
                                :href="action.href"
                                class="dash-quick-action"
                                :class="`dash-quick-action--${action.tone}`"
                            >
                                <span class="dash-quick-action__icon"><i class="fas" :class="action.icon"></i></span>
                                <span>
                                    <div class="dash-quick-action__title">{{ action.label }}</div>
                                    <div class="dash-quick-action__desc">{{ action.desc }}</div>
                                </span>
                            </Link>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Super Admin Setup -->
            <template v-else-if="dashboard.mode === 'super_admin_setup'">
                <div class="dash-stat-grid mb-4">
                    <div class="dash-stat-card dash-stat-card--primary">
                        <div class="dash-stat-card__icon"><i class="fas fa-building"></i></div>
                        <div>
                            <div class="dash-stat-card__value">{{ dashboard.stats.companies }}</div>
                            <div class="dash-stat-card__label">Your Companies</div>
                            <Link href="/companies" class="dash-stat-card__link">Manage <i class="fas fa-arrow-right"></i></Link>
                        </div>
                    </div>
                    <div class="dash-stat-card dash-stat-card--info">
                        <div class="dash-stat-card__icon"><i class="fas fa-code-branch"></i></div>
                        <div>
                            <div class="dash-stat-card__value">{{ dashboard.stats.branches }}</div>
                            <div class="dash-stat-card__label">Branches</div>
                            <Link href="/branches" class="dash-stat-card__link">Manage <i class="fas fa-arrow-right"></i></Link>
                        </div>
                    </div>
                    <div class="dash-stat-card dash-stat-card--success">
                        <div class="dash-stat-card__icon"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="dash-stat-card__value">{{ dashboard.stats.users }}</div>
                            <div class="dash-stat-card__label">Branch Users</div>
                            <Link href="/users" class="dash-stat-card__link">Manage <i class="fas fa-arrow-right"></i></Link>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header">
                                <h3 class="card-title mb-0">Company Setup</h3>
                            </div>
                            <div class="card-body">
                                <div class="dash-quick-grid">
                                    <Link
                                        v-for="action in setupActions"
                                        :key="action.href"
                                        :href="action.href"
                                        class="dash-quick-action"
                                        :class="`dash-quick-action--${action.tone}`"
                                    >
                                        <span class="dash-quick-action__icon"><i class="fas" :class="action.icon"></i></span>
                                        <span>
                                            <div class="dash-quick-action__title">{{ action.label }}</div>
                                            <div class="dash-quick-action__desc">{{ action.desc }}</div>
                                        </span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="dash-cta-card h-100 d-flex flex-column">
                            <h5 class="fw-semibold mb-2">Ready for daily work?</h5>
                            <p class="text-muted small mb-3">
                                Click a branch below to operate as Admin. You will get access to bilties, challans, and all branch operations.
                            </p>

                            <div v-if="selectableBranches.length" class="d-flex flex-column gap-2">
                                <button
                                    v-for="branch in selectableBranches"
                                    :key="branch.id"
                                    type="button"
                                    class="btn btn-brand-primary text-start d-flex justify-content-between align-items-center"
                                    @click="selectBranch(branch.id)"
                                >
                                    <span>
                                        <span class="d-block fw-semibold">{{ branch.name || 'Unnamed Branch' }}</span>
                                        <small class="opacity-75">{{ branch.company?.name }}</small>
                                    </span>
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                            <div v-else class="alert alert-warning mb-0">
                                No branches yet.
                                <Link href="/branches/create" class="alert-link ms-1">Create a branch</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Operational (Admin, Moderator, Accountant, Super Admin in branch) -->
            <template v-else>
                <div v-if="visibleStatCards.length" class="dash-stat-grid mb-4">
                    <div 
                        v-for="stat in visibleStatCards"
                        :key="stat.key"
                        class="dash-stat-card"
                        :class="`dash-stat-card--${stat.tone}`"
                    >
                        <div class="dash-stat-card__icon"><i class="fas" :class="stat.icon"></i></div>
                        <div>
                            <div class="dash-stat-card__value">{{ stat.value }}</div>
                            <div class="dash-stat-card__label">{{ stat.label }}</div>
                            <div v-if="stat.meta" class="dash-stat-card__meta">{{ stat.meta }}</div>
                            <Link v-if="stat.href" :href="stat.href" class="dash-stat-card__link">
                                View all <i class="fas fa-arrow-right"></i>
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-8">
                        <div v-if="can('view bilties')" class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0">Recent Bilties</h3>
                                <Link v-if="can('create bilties')" href="/bilties/create" class="btn btn-primary btn-sm ms-auto">
                                    <i class="fas fa-plus me-1"></i> New Bilty
                                </Link>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover m-0 text-sm">
                                        <thead>
                                            <tr>
                                                <th>Bilty No.</th>
                                                <th>Date</th>
                                                <th>Consignee</th>
                                                <th>Status</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="bilty in dashboard.recent_bilties" :key="bilty.id">
                                                <td>
                                                    <Link
                                                        v-if="can('edit bilties')"
                                                        :href="`/bilties/${bilty.id}/edit`"
                                                        class="fw-semibold"
                                                    >
                                                        {{ bilty.bilty_number || '—' }}
                                                    </Link>
                                                    <span v-else>{{ bilty.bilty_number || '—' }}</span>
                                                </td>
                                                <td>{{ formatDate(bilty.date) }}</td>
                                                <td>{{ bilty.consignee?.name || '—' }}</td>
                                                <td>
                                                    <span class="badge" :class="statusBadgeClass(bilty.status)">
                                                        {{ bilty.status || 'Booked' }}
                                                    </span>
                                                </td>
                                                <td class="text-end fw-semibold">{{ formatCurrency(bilty.amount) }}</td>
                                            </tr>
                                            <tr v-if="!dashboard.recent_bilties?.length">
                                                <td colspan="5" class="dash-empty">No recent bilties.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div v-if="dashboard.recent_bilties?.length" class="card-footer text-end">
                                <Link href="/bilties" class="btn btn-sm btn-brand-secondary">View All Bilties</Link>
                            </div>
                        </div>

                        <div v-if="can('view challans')" class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0">Recent Challans</h3>
                                <Link v-if="can('create challans')" href="/challans/create" class="btn btn-primary btn-sm ms-auto">
                                    <i class="fas fa-plus me-1"></i> New Challan
                                </Link>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover m-0 text-sm">
                                        <thead>
                                            <tr>
                                                <th>Challan No.</th>
                                                <th>Date</th>
                                                <th>Truck</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="challan in dashboard.recent_challans" :key="challan.id">
                                                <td>
                                                    <Link
                                                        v-if="can('edit challans')"
                                                        :href="`/challans/${challan.id}/edit`"
                                                        class="fw-semibold"
                                                    >
                                                        {{ challan.challan_number || '—' }}
                                                    </Link>
                                                    <span v-else>{{ challan.challan_number || '—' }}</span>
                                                </td>
                                                <td>{{ formatDate(challan.date) }}</td>
                                                <td>{{ challan.truck?.truck_number || '—' }}</td>
                                            </tr>
                                            <tr v-if="!dashboard.recent_challans?.length">
                                                <td colspan="3" class="dash-empty">No recent challans.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div v-if="dashboard.recent_challans?.length" class="card-footer text-end">
                                <Link href="/challans" class="btn btn-sm btn-brand-secondary">View All Challans</Link>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title mb-0">Quick Actions</h3>
                            </div>
                            <div class="card-body">
                                <div v-if="visibleQuickActions.length" class="dash-quick-grid" style="grid-template-columns: 1fr;">
                                    <Link
                                        v-for="action in visibleQuickActions"
                                        :key="action.href"
                                        :href="action.href"
                                        class="dash-quick-action"
                                        :class="`dash-quick-action--${action.tone}`"
                                    >
                                        <span class="dash-quick-action__icon"><i class="fas" :class="action.icon"></i></span>
                                        <span>
                                            <div class="dash-quick-action__title">{{ action.label }}</div>
                                            <div class="dash-quick-action__desc">{{ action.desc }}</div>
                                        </span>
                                    </Link>
                                </div>
                                <div v-else class="dash-empty py-4">No actions available for your role.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/composables/usePermissions';

const props = defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
});

const { can } = usePermissions();

const selectableBranches = computed(() => props.dashboard.selectable_branches || []);

const selectBranch = (branchId) => {
    router.post('/session/branch', { branch_id: branchId });
};

const portalActions = [
    { label: 'Manage Super Admins', desc: 'Create and manage platform super admins', href: '/users', icon: 'fa-users-cog', tone: 'primary' },
    { label: 'Roles & Permissions', desc: 'Configure role-based access', href: '/roles', icon: 'fa-user-shield', tone: 'info' },
];

const setupActions = [
    { label: 'Companies', desc: 'Register and manage companies', href: '/companies', icon: 'fa-building', tone: 'primary' },
    { label: 'Subscription', desc: 'Renew plan and purchase add-ons', href: '/subscription', icon: 'fa-credit-card', tone: 'success' },
    { label: 'Branches', desc: 'Add branches and head offices', href: '/branches', icon: 'fa-code-branch', tone: 'info' },
    { label: 'Users', desc: 'Invite branch staff', href: '/users', icon: 'fa-users', tone: 'success' },
    // { label: 'Subscriptions', desc: 'View subscription plans', href: '/subscriptions', icon: 'fa-credit-card', tone: 'secondary' },
    { label: 'Stations', desc: 'Shared route stations', href: '/stations', icon: 'fa-map-marker-alt', tone: 'secondary' },
    { label: 'Goods & Packages', desc: 'Shared master data', href: '/goods', icon: 'fa-box', tone: 'secondary' },
    { label: 'Truck Types', desc: 'Shared master data', href: '/truck-types', icon: 'fa-truck', tone: 'secondary' },
];

const quickActions = [
    { label: 'Create Bilty', desc: 'Lorry receipt', href: '/bilties/create', icon: 'fa-file-invoice', permission: 'create bilties', tone: 'primary' },
    { label: 'New Challan', desc: 'Trip sheet', href: '/challans/create', icon: 'fa-file-contract', permission: 'create challans', tone: 'warning' },
    { label: 'Main Challan', desc: 'Consolidated challan', href: '/main-challans/create', icon: 'fa-file-alt', permission: 'create challans', tone: 'info' },
    { label: 'Loading Report', desc: 'Print pending bilties', href: '/loading-reports', icon: 'fa-truck-loading', permission: 'view loading_reports', tone: 'info' },
    { label: 'Inward Register', desc: 'Goods inward entry', href: '/inward-registers/create', icon: 'fa-clipboard-list', permission: 'create inward_registers', tone: 'secondary' },
    // { label: 'Accounting Voucher', desc: 'Ledger entry', href: '/vouchers/create', icon: 'fa-file-invoice-dollar', permission: 'create vouchers', tone: 'success' },
    // { label: 'Bilty Register', desc: 'View report', href: '/reports/bilty-register', icon: 'fa-chart-bar', permission: 'view reports', tone: 'secondary' },
    // { label: 'Challan Register', desc: 'View report', href: '/reports/challan-register', icon: 'fa-chart-line', permission: 'view reports', tone: 'secondary' },
    // { label: 'Party Ledger', desc: 'View report', href: '/reports/party-ledger', icon: 'fa-book', permission: 'view reports', tone: 'secondary' },
];

const visibleQuickActions = computed(() =>
    quickActions.filter((action) => can(action.permission)),
);

const visibleStatCards = computed(() => {
    const stats = props.dashboard.stats || {};
    const cards = [];

    if (stats.bilties) {
        cards.push({
            key: 'bilties',
            value: stats.bilties.total,
            label: 'Total Bilties',
            meta: `${stats.bilties.today} today · ${stats.bilties.pending} pending`,
            icon: 'fa-file-invoice',
            tone: 'info',
            href: '/bilties',
        });
    }

    if (stats.challans) {
        cards.push({
            key: 'challans',
            value: stats.challans.total,
            label: 'Challans',
            meta: `${stats.challans.today} created today`,
            icon: 'fa-file-contract',
            tone: 'warning',
            href: '/challans',
        });
    }

    if (stats.main_challans) {
        cards.push({
            key: 'main_challans',
            value: stats.main_challans.total,
            label: 'Main Challans',
            meta: `${stats.main_challans.today} created today`,
            icon: 'fa-file-alt',
            tone: 'primary',
            href: '/main-challans',
        });
    }

    if (stats.inward_registers) {
        cards.push({
            key: 'inward_registers',
            value: stats.inward_registers.total,
            label: 'Inward Registers',
            meta: `${stats.inward_registers.today} today`,
            icon: 'fa-clipboard-list',
            tone: 'secondary',
            href: '/inward-registers',
        });
    }

    if (stats.vouchers) {
        cards.push({
            key: 'vouchers',
            value: stats.vouchers.total,
            label: 'Vouchers',
            meta: `${stats.vouchers.today} today`,
            icon: 'fa-file-invoice-dollar',
            tone: 'success',
            href: '/accounting/vouchers',
        });
    }

    return cards;
});

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatCurrency = (value) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(Number(value));
};

const statusBadgeClass = (status) => {
    const normalized = (status || '').toLowerCase();

    if (normalized === 'pending') {
        return 'bg-warning';
    }

    if (normalized === 'cancelled' || normalized === 'canceled') {
        return 'bg-danger';
    }

    return 'bg-success';
};
</script>
