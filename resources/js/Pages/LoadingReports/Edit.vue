<template>
    <AdminLayout title="Edit Loading Report">
        <div class="row">
            <div class="col-md-12 mx-auto">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Edit Loading Report</h3>
                    </div>

                    <form @submit.prevent="submit" class="bilty-form">
                        <div class="card-body pt-3 pb-2 border-bottom">
                            <div class="row align-items-center mb-2">
                                <div class="col-md-4 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 90px;">Date</label>
                                    <input type="date" class="form-control form-control-sm border-secondary shadow-none" v-model="form.date">
                                </div>
                                <div class="col-md-4 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 90px;">Truck No.</label>
                                    <SearchableSelect
    :small="true"
    v-model="form.truck_id"
    :options="trucks"
    labelKey="truck_no"
    placeholder="Select Truck"
/>
                                </div>
                                <div class="col-md-4 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 90px;">Driver</label>
                                    <SearchableSelect
    :small="true"
    v-model="form.driver_id"
    :options="drivers"
    placeholder="Select Driver"
/>
                                </div>
                            </div>
                            
                            <div class="row align-items-center mb-2">
                                <div class="col-md-6 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 90px;">From St.</label>
                                    <SearchableSelect
    :small="true"
    v-model="form.from_station_id"
    :options="stations"
    placeholder="Select Station"
/>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold text-dark" style="min-width: 90px;">To St.</label>
                                    <SearchableSelect
    :small="true"
    v-model="form.to_station_id"
    :options="stations"
    placeholder="Select Station"
/>
                                </div>
                            </div>
                        </div>

                        <!-- Grid Section: Loaded Bilties -->
                        <div class="card-body p-0 border-bottom">
                            <div class="table-responsive" style="min-height: 200px;">
                                <table class="table table-bordered table-sm m-0 table-grid">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="5%" class="text-center">Sr.No.</th>
                                            <th width="20%">Bilty No.</th>
                                            <th width="15%">Consignee</th>
                                            <th width="20%">Goods</th>
                                            <th width="15%" class="text-center">Packages</th>
                                            <th width="15%" class="text-center">Weight(Kg)</th>
                                            <th width="10%" class="text-center"><i class="fas fa-cog"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(detail, index) in form.details" :key="index" class="bg-white">
                                            <td class="text-center align-middle">{{ index + 1 }}</td>
                                            <td>
                                                <SearchableSelect
    :small="true"
    @change="fetchBiltyDetails(index)"
    v-model="detail.bilty_id"
    :options="bilties"
    labelKey="bilty_number"
    placeholder="Select Bilty"
/>
                                            </td>
                                            <td class="align-middle">
                                                {{ detail.consignee_name || '--' }}
                                            </td>
                                            <td class="align-middle">
                                                {{ detail.goods_name || '--' }}
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm border-0 shadow-none text-center bg-transparent" v-model="detail.no_of_packages" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm border-0 shadow-none text-center bg-transparent" v-model="detail.gross_weight" readonly>
                                            </td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="removeRow(index)" v-if="form.details.length > 1">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="7" class="bg-light p-1 text-center">
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0" @click="addRow">
                                                    <i class="fas fa-plus"></i> Add Bilty
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Bottom Section -->
                        <div class="card-body pt-3 pb-2">
                            <div class="row align-items-center mb-1">
                                <div class="col-md-2 text-end"><label class="mb-0 fw-bold small text-dark">Total Packages</label></div>
                                <div class="col-md-2"><input type="number" class="form-control form-control-sm border-secondary shadow-none" :value="totalPackages" readonly></div>
                                
                                <div class="col-md-2 text-end"><label class="mb-0 fw-bold small text-dark">Total Weight</label></div>
                                <div class="col-md-2"><input type="number" step="0.01" class="form-control form-control-sm border-secondary shadow-none" :value="totalWeight" readonly></div>
                            </div>
                            <div class="row align-items-start mt-3">
                                <div class="col-md-2 text-end"><label class="mb-0 fw-bold small text-dark mt-1">Remarks</label></div>
                                <div class="col-md-10"><textarea class="form-control form-control-sm border-secondary shadow-none" rows="2" v-model="form.remark"></textarea></div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card-footer bg-light d-flex justify-content-center gap-2 border-top-0 pt-3 pb-3">
                            <Link href="/loading-reports" class="btn btn-outline-secondary px-4"><i class="fas fa-times me-1"></i> Exit</Link>
                            <button type="submit" class="btn btn-warning px-5 fw-bold shadow-sm" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, onMounted } from 'vue';

const props = defineProps({
    report: Object,
    stations: Array,
    trucks: Array,
    drivers: Array,
    bilties: Array
});

const initialDetails = (props.report.details && props.report.details.length > 0)
    ? props.report.details.map(d => ({
        bilty_id: d.bilty_id || '',
        good_id: d.good_id || '',
        package_type_id: d.package_type_id || '',
        consignee_id: d.consignee_id || '',
        consignee_name: '', // Will populate on mount
        goods_name: '', // Will populate on mount
        no_of_packages: d.no_of_packages || '',
        gross_weight: d.gross_weight || ''
    }))
    : [{ bilty_id: '', good_id: '', package_type_id: '', consignee_id: '', consignee_name: '', goods_name: '', no_of_packages: '', gross_weight: '' }];


const form = useForm({
    from_station_id: props.report.from_station_id || '',
    to_station_id: props.report.to_station_id || '',
    truck_id: props.report.truck_id || '',
    driver_id: props.report.driver_id || '',
    date: props.report.date || new Date().toISOString().substr(0, 10),
    remark: props.report.remark || '',
    details: initialDetails
});

onMounted(() => {
    // Populate names for initial details based on bilty lookups
    form.details.forEach((row, index) => {
        if(row.bilty_id) fetchBiltyDetails(index);
    });
});

const addRow = () => {
    form.details.push({ bilty_id: '', good_id: '', package_type_id: '', consignee_id: '', consignee_name: '', goods_name: '', no_of_packages: '', gross_weight: '' });
};

const removeRow = (index) => {
    form.details.splice(index, 1);
};

const fetchBiltyDetails = (index) => {
    const row = form.details[index];
    const selectedBilty = props.bilties.find(b => b.id === row.bilty_id);
    if (selectedBilty) {
        row.consignee_id = selectedBilty.consignee_id;
        row.consignee_name = selectedBilty.consignee ? selectedBilty.consignee.name : 'Unknown';
        
        if(selectedBilty.bilty_details && selectedBilty.bilty_details.length > 0) {
            const bDetail = selectedBilty.bilty_details[0];
            row.good_id = bDetail.good_id;
            row.package_type_id = bDetail.package_type_id;
            row.goods_name = 'Good ID: ' + bDetail.good_id; 
            
            let sumPkg = 0;
            let sumWt = 0;
            selectedBilty.bilty_details.forEach(d => {
                sumPkg += parseFloat(d.no_of_packages || 0);
                sumWt += parseFloat(d.gross_weight || 0);
            });
            row.no_of_packages = sumPkg;
            row.gross_weight = sumWt.toFixed(2);
        }
    } else {
        row.consignee_id = '';
        row.consignee_name = '';
        row.good_id = '';
        row.package_type_id = '';
        row.goods_name = '';
        row.no_of_packages = '';
        row.gross_weight = '';
    }
};

const totalPackages = computed(() => {
    return form.details.reduce((sum, row) => sum + parseFloat(row.no_of_packages || 0), 0);
});

const totalWeight = computed(() => {
    return form.details.reduce((sum, row) => sum + parseFloat(row.gross_weight || 0), 0).toFixed(2);
});

const submit = () => {
    form.put(`/loading-reports/${props.report.id}`);
};
</script>

<style scoped>
.bilty-form {
    font-size: 0.85rem;
}
.bilty-form label {
    font-size: 0.85rem;
    white-space: nowrap;
}
.bilty-form .form-control-sm, .bilty-form .form-select-sm {
    font-size: 0.85rem;
    padding: 0.25rem 0.5rem;
}
.table-grid th, .table-grid td {
    border-color: #dee2e6;
    font-size: 0.85rem;
}
</style>
