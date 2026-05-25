<template>
    <AdminLayout title="Dashboard">
        <!-- KPI Row -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ stats.total_bilties }}</h3>
                        <p>Total Bilties ({{ stats.today_bilties }} Today)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <Link href="/bilties" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></Link>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>₹{{ formatCurrency(stats.total_freight) }}</h3>
                        <p>Total Booked Freight</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <Link href="/bilties" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></Link>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ stats.total_challans }}</h3>
                        <p>Total Challans ({{ stats.today_challans }} Today)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-truck-moving"></i>
                    </div>
                    <Link href="/challans" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></Link>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ stats.active_trucks }}</h3>
                        <p>Registered Trucks</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <Link href="/trucks" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></Link>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Recent Bilties -->
            <div class="col-lg-8">
                <div class="card card-primary card-outline">
                    <div class="card-header border-transparent">
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
                                    <tr v-if="stats.recent_bilties.length === 0">
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
            
            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card card-warning card-outline">
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
    </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    stats: Object
});

const formatCurrency = (value) => {
    return Number(value).toLocaleString('en-IN', {
        maximumFractionDigits: 2,
        minimumFractionDigits: 2
    });
};
</script>
