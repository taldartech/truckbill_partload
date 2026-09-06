<template>
    <AdminLayout title="Bilty Register">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-filter me-2"></i>Filter Bilty Register</h3>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="filterReports" class="row">
                            <div class="col-md-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.start_date">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.end_date">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Station</label>
                                <SearchableSelect
    :small="true"
    v-model="form.station_id"
    :options="stations"
    placeholder="All Stations"
/>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Party</label>
                                <SearchableSelect
    :small="true"
    v-model="form.party_id"
    :options="parties"
    placeholder="All Parties"
/>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover table-bordered text-sm m-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Bilty No.</th>
                                    <th>From Station</th>
                                    <th>To Station</th>
                                    <th>Consignor</th>
                                    <th>Consignee</th>
                                    <th>Truck No</th>
                                    <th>Payment Type</th>
                                    <th class="text-end">Total Freight</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="bilty in bilties" :key="bilty.id">
                                    <td class="text-nowrap">{{ bilty.date }}</td>
                                    <td>
                                        <BiltyPrintButton
                                            :bilty-id="bilty.id"
                                            variant="link"
                                            :link-text="bilty.bilty_number"
                                            title="Print Bilty"
                                        />
                                    </td>
                                    <td>{{ bilty.from_station?.name }}</td>
                                    <td>{{ bilty.to_station?.name }}</td>
                                    <td>{{ bilty.consignor?.name }}</td>
                                    <td>{{ bilty.consignee?.name }}</td>
                                    <td>{{ bilty.truck?.truck_no || 'N/A' }}</td>
                                    <td>{{ bilty.payment_type }}</td>
                                    <td class="text-end fw-bold text-success">₹ {{ bilty.amount }}</td>
                                </tr>
                                <tr v-if="bilties.length === 0">
                                    <td colspan="9" class="text-center py-4 text-muted">No bilties found for selected filters.</td>
                                </tr>
                            </tbody>
                            <tfoot v-if="bilties.length > 0">
                                <tr>
                                    <td colspan="8" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold text-success">₹ {{ totalFreight.toFixed(2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import BiltyPrintButton from '@/Components/BiltyPrintButton.vue';
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    bilties: Array,
    stations: Array,
    parties: Array,
    filters: Object
});

const form = useForm({
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    station_id: props.filters?.station_id || '',
    party_id: props.filters?.party_id || ''
});

const filterReports = () => {
    form.get('/reports/bilty-register', {
        preserveState: true
    });
};

const totalFreight = computed(() => {
    return props.bilties.reduce((sum, bilty) => sum + parseFloat(bilty.amount || 0), 0);
});
</script>
