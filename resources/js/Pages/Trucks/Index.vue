<template>
    <AdminLayout title="Trucks">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Manage Trucks</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <button type="button" class="btn btn-sm app-index-header-btn" @click="showBulkUpload = true" v-if="can('create trucks')">
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--upload" aria-hidden="true"></span>
                            Bulk Upload
                        </button>
                        <Link v-if="can('create trucks')" href="/trucks/create" class="btn btn-sm app-index-header-btn">
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus" aria-hidden="true"></span>
                            Create
                        </Link>
                    </div>
                </div>

                <AppIndexCollapsibleFilters
                    panel-id="trucks-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col">
                        <label class="form-label" for="trucks_search">Search</label>
                        <AppIndexSearchInput
                            v-model="filters.search"
                            input-id="trucks_search"
                            placeholder="Truck no, owner, status"
                            max-width="100%"
                            size="md"
                            :disabled="searching"
                            @keydown.enter="applyFilters"
                        />
                    </div>
                </AppIndexCollapsibleFilters>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Truck Number</th>
                                    <th>Truck Type</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                    <th>Registration Date</th>
                                    <th>Truck Owner</th>
                                    <th class="text-start" v-if="can('delete trucks') || can('edit trucks')">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="truck in trucks.data" :key="truck.id">
                                    <td>
                                        <Link :href="`/trucks/${truck.id}`" class="text-decoration-none fw-bold text-brand-primary">
                                            {{ truck.truck_number }}
                                        </Link>
                                    </td>
                                    <td>{{ truck.truck_type?.name || 'N/A' }}</td>
                                    <td>
                                        <span class="badge" :class="{
                                            'bg-warning text-dark': truck.status === 'Booked',
                                            'bg-info': truck.status === 'In Transit',
                                            'bg-secondary': truck.status === 'Maintenance',
                                            'bg-success': !truck.status || truck.status === 'Available',
                                        }">{{ truck.status || 'Available' }}</span>
                                    </td>
                                    <td>
                                        <span v-if="truck.current_city">
                                            {{ truck.currentCity?.name }}<span v-if="truck.currentStation">, {{ truck.currentStation?.name }}</span>
                                        </span>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                    <td>{{ truck.rc_expiry_date || '—' }}</td>
                                    <td>{{ truck.truck_owner?.name || truck.truckOwner?.name || '—' }}</td>
                                    <td class="text-start" v-if="can('delete trucks') || can('edit trucks')">
                                        <button
                                            v-if="can('edit trucks')"
                                            type="button"
                                            class="btn btn-view btn-sm me-2"
                                            title="Update Status & Location"
                                            @click="openTourModal(truck)"
                                        >
                                            <i class="fas fa-map-marker-alt"></i>
                                        </button>
                                        <Link
                                            v-if="can('edit trucks')"
                                            :href="`/trucks/${truck.id}/edit`"
                                            class="btn btn-edit btn-sm me-2 my-1"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button
                                            v-if="can('delete trucks')"
                                            type="button"
                                            class="btn btn-sm btn-danger"
                                            @click="deleteTruck(truck)"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="trucks.data.length === 0">
                                    <td colspan="9" class="text-center text-muted py-4">No trucks found for selected filters.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <PaginationLinks :pagination="trucks" />
            </div>
        </div>

        <div class="modal fade" id="tourModal" tabindex="-1" aria-labelledby="tourModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form @submit.prevent="submitTour">
                        <div class="modal-header">
                            <h5 class="modal-title" id="tourModalLabel">Update Status & Location — {{ selectedTruck?.truck_number }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" v-model="tourForm.status" required>
                                    <option value="Available">Available</option>
                                    <option value="Booked">Booked</option>
                                    <option value="In Transit">In Transit</option>
                                    <option value="Maintenance">Maintenance</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">State</label>
                                <SearchableSelect v-model="tourForm.current_state_id" :options="states" placeholder="Select State" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">City</label>
                                <select class="form-select" v-model="tourForm.current_city_id" required>
                                    <option value="">Select City</option>
                                    <option v-for="city in filteredCities" :key="city.id" :value="city.id">{{ city.name }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Station (Optional)</label>
                                <select class="form-select" v-model="tourForm.current_station_id">
                                    <option value="">Select Station</option>
                                    <option v-for="station in filteredStations" :key="station.id" :value="station.id">{{ station.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-back" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="tourForm.processing">Update Truck</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <BulkUploadModal
            v-model:show="showBulkUpload"
            title="Bulk Upload Trucks"
            upload-url="/trucks/bulk-upload"
            template-download-url="/trucks/bulk-upload/template"
            template-file-name="trucks_bulk_upload_template.csv"
            :columns="bulkUploadColumns"
            :sample-data="bulkUploadSampleData"
            @success="onBulkUploadSuccess"
        />
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router, usePage, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppIndexCollapsibleFilters from '@/Components/Index/AppIndexCollapsibleFilters.vue';
import AppIndexSearchInput from '@/Components/Index/AppIndexSearchInput.vue';
import BulkUploadModal from '@/Components/BulkUploadModal.vue';
import { useMasterIndexSearch } from '@/composables/useMasterIndexSearch';
import { askKeyboardConfirmWithRemark } from '@/composables/useKeyboardConfirm';

const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

const props = defineProps({
    trucks: Object,
    states: Array,
    cities: Array,
    stations: Array,
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const { searching, filters, applyFilters, resetFilters } = useMasterIndexSearch(props, '/trucks');

let modalInstance = null;
const selectedTruck = ref(null);

onMounted(() => {
    if (typeof bootstrap !== 'undefined') {
        modalInstance = new bootstrap.Modal(document.getElementById('tourModal'));
    }
});

const tourForm = useForm({
    status: '',
    current_state_id: '',
    current_city_id: '',
    current_station_id: '',
});

const filteredCities = computed(() => {
    if (!tourForm.current_state_id) return [];
    return props.cities.filter(c => String(c.state_id) === String(tourForm.current_state_id));
});

const filteredStations = computed(() => {
    if (!tourForm.current_city_id) return [];
    return props.stations.filter(s => String(s.city_id) === String(tourForm.current_city_id));
});

const deleteTruck = async (truck) => {
    const remark = await askKeyboardConfirmWithRemark(
        `Are you sure you want to delete this truck?

Truck : ${truck.truck_number}`
    );

    if (!remark) return;

    router.delete(`/trucks/${truck.id}`, {
        data: {
            delete_remark: remark,
        },
    });
};

const openTourModal = (truck) => {
    selectedTruck.value = truck;
    tourForm.status = truck.status || 'Available';
    tourForm.current_state_id = truck.current_state_id || '';
    tourForm.current_city_id = truck.current_city_id || '';
    tourForm.current_station_id = truck.current_station_id || '';
    if (modalInstance) modalInstance.show();
};

const submitTour = () => {
    tourForm.put(`/trucks/${selectedTruck.value.id}/complete-tour`, {
        onSuccess: () => {
            if (modalInstance) modalInstance.hide();
            tourForm.reset();
        },
    });
};

const showBulkUpload = ref(false);

const bulkUploadColumns = [
    { name: 'truck_number', label: 'Truck Number', required: true, description: 'Registration Number (e.g. MH04AB1234)' },
    { name: 'truck_type', label: 'Truck Type', required: false, description: 'Type name (e.g. 14 Ft Open Body)' },
    { name: 'capacity', label: 'Capacity', required: false, description: 'Capacity (e.g. 15 Tons)' },
    { name: 'status', label: 'Status', required: false, description: 'Available / Booked / In Transit / Maintenance' },
    { name: 'state', label: 'State', required: false, description: 'Current State' },
    { name: 'city', label: 'City', required: false, description: 'Current City (must exist in master records)' },
    { name: 'station', label: 'Station', required: false, description: 'Current Station' },
    { name: 'truck_owner', label: 'Truck Owner', required: false, description: 'Truck Owner Name' },
    { name: 'driver', label: 'Driver Name', required: false, description: 'Driver Name (optional, not stored)' },
    { name: 'rc_no', label: 'RC Number', required: false, description: 'RC Number' },
    { name: 'rc_expiry_date', label: 'RC Expiry Date', required: false, description: 'YYYY-MM-DD' },
    { name: 'insurance_no', label: 'Insurance No', required: false, description: 'Insurance Policy Number' },
    { name: 'insurance_expiry_date', label: 'Insurance Expiry Date', required: false, description: 'YYYY-MM-DD' },
    { name: 'fitness_no', label: 'Fitness No', required: false, description: 'Fitness Certificate No' },
    { name: 'fitness_expiry_date', label: 'Fitness Expiry Date', required: false, description: 'YYYY-MM-DD' },
    { name: 'pollution_no', label: 'Pollution No', required: false, description: 'PUC Number' },
    { name: 'pollution_expiry_date', label: 'Pollution Expiry Date', required: false, description: 'YYYY-MM-DD' },
    { name: 'permit_no', label: 'Permit No', required: false, description: 'Permit Number' },
    { name: 'permit_expiry_date', label: 'Permit Expiry Date', required: false, description: 'YYYY-MM-DD' },
];

</script>
