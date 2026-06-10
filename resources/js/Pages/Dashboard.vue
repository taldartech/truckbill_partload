<template>
    <AdminLayout title="Dashboard">
        <template v-if="portal_mode === 'master_admin'">
            <div class="row">
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>{{ portal_stats.super_admins }}</h3>
                            <p>Super Admins</p>
                        </div>
                        <div class="icon"><i class="fas fa-user-shield"></i></div>
                        <Link href="/users" class="small-box-footer">Manage Super Admins <i class="fas fa-arrow-circle-right"></i></Link>
                    </div>
                </div>
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ portal_stats.companies }}</h3>
                            <p>Registered Companies</p>
                        </div>
                        <div class="icon"><i class="fas fa-building"></i></div>
                        <span class="small-box-footer">Platform overview</span>
                    </div>
                </div>
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ portal_stats.active_subscriptions }}</h3>
                            <p>Active Subscriptions</p>
                        </div>
                        <div class="icon"><i class="fas fa-credit-card"></i></div>
                        <Link href="/subscriptions" class="small-box-footer">Manage Subscriptions <i class="fas fa-arrow-circle-right"></i></Link>
                    </div>
                </div>
            </div>

            <div class="card card-outline">
                <div class="card-header">
                    <h3 class="card-title">Portal Administration</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        You are logged in as <strong>Master Admin</strong>. Manage Super Admins, roles, and subscriptions. Company operations such as bilties and challans are handled by Super Admins and branch users.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <Link href="/users" class="btn btn-primary"><i class="fas fa-users-cog me-1"></i> Manage Super Admins</Link>
                        <Link href="/roles" class="btn btn-outline-primary"><i class="fas fa-user-shield me-1"></i> Roles & Permissions</Link>
                        <Link href="/subscriptions" class="btn btn-outline-success"><i class="fas fa-credit-card me-1"></i> Subscriptions</Link>
                    </div>
                </div>
            </div>
        </template>

        <template v-else-if="portal_mode === 'super_admin'">
            <div class="row">
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>{{ portal_stats.companies }}</h3>
                            <p>Your Companies</p>
                        </div>
                        <div class="icon"><i class="fas fa-building"></i></div>
                        <Link href="/companies" class="small-box-footer">Manage Companies <i class="fas fa-arrow-circle-right"></i></Link>
                    </div>
                </div>
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ portal_stats.branches }}</h3>
                            <p>Branches</p>
                        </div>
                        <div class="icon"><i class="fas fa-code-branch"></i></div>
                        <Link href="/branches" class="small-box-footer">Manage Branches <i class="fas fa-arrow-circle-right"></i></Link>
                    </div>
                </div>
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ portal_stats.users }}</h3>
                            <p>Branch Users</p>
                        </div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <Link href="/users" class="small-box-footer">Manage Users <i class="fas fa-arrow-circle-right"></i></Link>
                    </div>
                </div>
            </div>

            <div class="card card-outline">
                <div class="card-header">
                    <h3 class="card-title">Company Setup</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Register companies, branches, and users. When you are ready for day-to-day work, select a branch to act as Admin.
                    </p>
                    <Link href="/select-branch" class="btn btn-warning">
                        <i class="fas fa-code-branch me-1"></i> Select Branch to Operate
                    </Link>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ stats.total_bilties }}</h3>
                            <p>Total Bilties ({{ stats.today_bilties }} Today)</p>
                        </div>
                        <div class="icon"><i class="fas fa-file-invoice"></i></div>
                        <Link href="/bilties" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></Link>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ stats.available_trucks }}</h3>
                            <p>Available Trucks</p>
                        </div>
                        <div class="icon"><i class="fas fa-truck-loading"></i></div>
                        <Link href="/trucks" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></Link>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ stats.total_challans }}</h3>
                            <p>Total Challans ({{ stats.today_challans }} Today)</p>
                        </div>
                        <div class="icon"><i class="fas fa-truck-moving"></i></div>
                        <Link href="/challans" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></Link>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3>{{ stats.pending_bilties }}</h3>
                            <p>Pending Bilties</p>
                        </div>
                        <div class="icon"><i class="fas fa-clock"></i></div>
                        <Link href="/bilties" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></Link>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Recent Bilties</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped m-0 text-sm">
                                    <thead>
                                        <tr>
                                            <th>Bilty No.</th>
                                            <th>Date</th>
                                            <th>Consignee</th>
                                            <th>Status</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="bilty in stats.recent_bilties" :key="bilty.id">
                                            <td><Link :href="`/bilties/${bilty.id}/edit`">{{ bilty.bilty_number }}</Link></td>
                                            <td>{{ bilty.date }}</td>
                                            <td>{{ bilty.consignee?.name || '--' }}</td>
                                            <td><span class="badge bg-success">Booked</span></td>
                                            <td class="fw-bold">₹ {{ bilty.amount }}</td>
                                        </tr>
                                        <tr v-if="!stats.recent_bilties?.length">
                                            <td colspan="5" class="text-center text-muted py-3">No recent bilties.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer clearfix">
                            <Link href="/bilties/create" class="btn btn-sm btn-primary float-start">Create New Bilty</Link>
                            <Link href="/bilties" class="btn btn-sm btn-secondary float-end">View All Bilties</Link>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-grid gap-2">
                                <Link href="/bilties/create" class="btn btn-primary text-start">
                                    <i class="fas fa-file-invoice me-2"></i> Create Bilty (Lorry Receipt)
                                </Link>
                                <Link href="/challans/create" class="btn btn-warning text-start">
                                    <i class="fas fa-truck-moving me-2"></i> Generate Challan (Trip Sheet)
                                </Link>
                                <Link href="/loading-reports/create" class="btn btn-info text-start">
                                    <i class="fas fa-truck-loading me-2"></i> New Loading Report
                                </Link>
                                <Link href="/vouchers/create" class="btn btn-success text-start">
                                    <i class="fas fa-file-invoice-dollar me-2"></i> Add Accounting Voucher
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    portal_mode: {
        type: String,
        default: null,
    },
    portal_stats: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});
</script>
