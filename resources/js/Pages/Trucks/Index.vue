<template>
    <AdminLayout title="Truck Management">
        <div class="row">
            <div class="col-12">
                <div class="card rounded-3 shadow-sm">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold">Trucks List</h5>
                        <Link v-if="can('create trucks')" href="/trucks/create" class="btn btn-primary rounded-pill px-4">
                            <i class="fas fa-plus me-1"></i> Add Truck
                        </Link>
                    </div>
                    <div class="card-body p-0">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light bg-opacity-50">
                            <div class="d-flex align-items-center">
                                <span class="small text-muted me-2">Show</span>
                                <select class="form-select form-select-sm" style="width: 70px;">
                                    <option>10</option>
                                    <option>25</option>
                                    <option>50</option>
                                </select>
                                <span class="small text-muted ms-2">entries</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="small text-muted me-2">Search:</span>
                                <input type="text" class="form-control form-control-sm" style="width: 200px;">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Sr. No</th>
                                        <th>Truck Number</th>
                                        <th>Truck Type</th>
                                        <th>Status</th>
                                        <th>Location</th>
                                        <th>Registration Date</th>
                                        <th>Truck Owner</th>
                                        <th>Driver</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(truck, index) in trucks.data" :key="truck.id">
                                        <td class="ps-4 text-muted">{{ index + 1 }}</td>
                                        <td>
                                            <Link :href="`/trucks/${truck.id}`" class="text-decoration-none fw-bold text-brand-primary">
                                                {{ truck.truck_number }}
                                            </Link>
                                        </td>
                                        <td>{{ truck.truck_type?.name || 'N/A' }}</td>
                                        <td>
                                            <span class="badge" :class="truck.status === 'Booked' ? 'bg-warning text-dark' : (truck.status === 'In Transit' ? 'bg-info text-dark' : 'bg-success')">
                                                {{ truck.status || 'Available' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div v-if="truck.current_city">
                                                {{ truck.currentCity?.name }}<span v-if="truck.currentStation">, {{ truck.currentStation?.name }}</span>
                                            </div>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td class="text-muted">{{ truck.rc_expiry_date || '-' }}</td>
                                        <td>{{ truck.transporter?.name || '-' }}</td>
                                        <td>{{ truck.driver?.name || '-' }}</td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <button @click="openTourModal(truck)" class="btn btn-primary btn-sm me-2 rounded-2" title="Update Status & Location">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </button>
                                                <Link :href="`/trucks/${truck.id}/edit`" class="btn btn-success btn-sm me-2 rounded-2">
                                                    <i class="fas fa-user-edit"></i>
                                                </Link>
                                                <button @click="deleteTruck(truck.id)" class="btn btn-danger btn-sm rounded-2">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="trucks.data.length === 0">
                                        <td colspan="7" class="text-center py-5 text-muted">No trucks found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top d-flex justify-content-between align-items-center">
                            <div class="small text-muted">
                                Showing 1 to {{ trucks.data.length }} of {{ trucks.total }} entries
                            </div>
                            <nav v-if="trucks.links.length > 3">
                                <ul class="pagination pagination-sm mb-0">
                                    <li v-for="link in trucks.links" :key="link.label" class="page-item" :class="{ 'active': link.active, 'disabled': !link.url }">
                                        <Link class="page-link" :href="link.url || '#'" v-html="link.label"></Link>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Complete Tour Modal -->
        <div class="modal fade" id="tourModal" tabindex="-1" aria-labelledby="tourModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form @submit.prevent="submitTour">
                        <div class="modal-header">
                            <h5 class="modal-title" id="tourModalLabel">Update Status & Location - {{ selectedTruck?.truck_number }}</h5>
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
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="tourForm.processing">Update Truck</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router, usePage, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';


const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

const props = defineProps({
    trucks: Object,
    states: Array,
    cities: Array,
    stations: Array
});

const deleteTruck = (id) => {
    if (confirm('Are you sure you want to delete this truck?')) {
        router.delete(`/trucks/${id}`);
    }
};

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
    current_station_id: ''
});

const filteredCities = computed(() => {
    if (!tourForm.current_state_id) return [];
    return props.cities.filter(c => String(c.state_id) === String(tourForm.current_state_id));
});

const filteredStations = computed(() => {
    if (!tourForm.current_city_id) return [];
    return props.stations.filter(s => String(s.city_id) === String(tourForm.current_city_id));
});

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
        }
    });
};
</script>
