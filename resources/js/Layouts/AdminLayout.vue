<template>
    <div class="app-wrapper bg-light">
        <Navbar />
        <Sidebar />
        <main class="app-main py-4">
            <!-- <div class="app-content-header mb-4">
                <div class="container-fluid px-4">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <h4 class="mb-0 fw-bold text-dark">{{ title }}</h4>
                        </div>
                    </div>
                </div>
            </div> -->
            <div class="app-content">
                <div class="container-fluid px-4">
                    <slot />
                </div>
            </div>
        </main>
        <Footer />

        <!-- Global Shortcuts Modal (F3) -->
        <Transition name="fade">
            <div v-if="isShortcutModalOpen" class="shortcut-modal-overlay" @click.self="closeShortcutModal">
                <div class="shortcut-modal-card">
                    <div class="shortcut-modal-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-keyboard text-success fs-4"></i>
                            <h5 class="m-0 fw-bold">Quick Navigation Shortcuts</h5>
                        </div>
                        <button class="btn-close-custom" @click="closeShortcutModal" aria-label="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="shortcut-modal-body">
                        <p class="text-muted small mb-4">Press the shortcut key on your keyboard or click an option below to open the form.</p>
                        
                        <div class="shortcut-list">
                            <!-- Bilty -->
                            <button 
                                v-if="canCreateBilty"
                                class="shortcut-item" 
                                @click="navigateTo('/bilties/create')"
                            >
                                <span class="shortcut-item-info">
                                    <i class="fas fa-file-invoice text-success icon-frame"></i>
                                    <span class="shortcut-item-name">Create Bilty (Booking)</span>
                                </span>
                                <span class="shortcut-item-badge">F9</span>
                            </button>

                            <!-- Challan -->
                            <button 
                                v-if="canCreateChallan"
                                class="shortcut-item" 
                                @click="navigateTo('/challans/create')"
                            >
                                <span class="shortcut-item-info">
                                    <i class="fas fa-file-contract text-warning icon-frame"></i>
                                    <span class="shortcut-item-name">Create Challan</span>
                                </span>
                                <span class="shortcut-item-badge">F7</span>
                            </button>

                            <!-- Main Challan -->
                            <button 
                                v-if="canCreateChallan"
                                class="shortcut-item" 
                                @click="navigateTo('/main-challans/create')"
                            >
                                <span class="shortcut-item-info">
                                    <i class="fas fa-file-alt text-info icon-frame"></i>
                                    <span class="shortcut-item-name">Create Main Challan</span>
                                </span>
                                <span class="shortcut-item-badge">F8</span>
                            </button>

                            <!-- Inward Register -->
                            <button 
                                v-if="canCreateInward"
                                class="shortcut-item" 
                                @click="navigateTo('/inward-registers/create')"
                            >
                                <span class="shortcut-item-info">
                                    <i class="fas fa-clipboard-list text-secondary icon-frame"></i>
                                    <span class="shortcut-item-name">Create Inward Register</span>
                                </span>
                                <span class="shortcut-item-badge">F5</span>
                            </button>

                            <!-- Delivery Receipt -->
                            <button 
                                v-if="canCreateDelivery"
                                class="shortcut-item" 
                                @click="navigateTo('/delivery-receipts/create')"
                            >
                                <span class="shortcut-item-info">
                                    <i class="fas fa-receipt text-danger icon-frame"></i>
                                    <span class="shortcut-item-name">Create Delivery Receipt</span>
                                </span>
                                <span class="shortcut-item-badge">F6</span>
                            </button>

                            <!-- Voucher -->
                            <button 
                                v-if="canCreateVoucher"
                                class="shortcut-item" 
                                @click="navigateTo('/vouchers/create')"
                            >
                                <span class="shortcut-item-info">
                                    <i class="fas fa-file-invoice-dollar text-primary icon-frame"></i>
                                    <span class="shortcut-item-name">Create Voucher</span>
                                </span>
                                <span class="shortcut-item-badge">F10</span>
                            </button>
                        </div>
                    </div>
                    <div class="shortcut-modal-footer">
                        <span class="text-muted small"><kbd class="bg-dark text-white px-2 py-1 rounded">ESC</kbd> to close</span>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Form Submission Summary Modal -->
        <Transition name="fade">
            <div v-if="isSummaryModalOpen" class="shortcut-modal-overlay" @click.self="closeSummaryModal">
                <div class="shortcut-modal-card summary-modal-card">
                    <div class="shortcut-modal-header bg-success text-white py-2 px-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-clipboard-check fs-5 text-white"></i>
                            <h6 class="m-0 fw-bold text-white">Confirm Details</h6>
                        </div>
                        <button class="btn-close-custom text-white" @click="closeSummaryModal" aria-label="Close" style="color: white !important;">
                            <i class="fas fa-times text-white"></i>
                        </button>
                    </div>
                    <div class="shortcut-modal-body summary-modal-body bg-white p-3">
                        <div class="container-fluid p-0">
                            <div class="row g-3">
                                <!-- Left Column: Document Info & Cargo Grid -->
                                <div class="col-md-7 d-flex flex-column gap-3">
                                    <!-- 1. Header Information Section -->
                                    <div class="summary-section">
                                        <h6 class="summary-section-title text-uppercase text-success fw-bold mb-2 d-flex align-items-center gap-2" style="font-size: 0.8rem;">
                                            <i class="fas fa-info-circle"></i> Document Details
                                        </h6>
                                        <div class="row g-2">
                                            <div v-for="field in headerFields" :key="field.rawKey" class="col-6 col-sm-4">
                                                <div class="header-info-box p-2 rounded border bg-light">
                                                    <span class="d-block text-muted text-uppercase fw-semibold mb-1" style="font-size: 0.65rem; letter-spacing: 0.05em;">{{ field.label }}</span>
                                                    <span class="d-block text-dark fw-bold text-truncate" style="font-size: 0.85rem;">{{ field.value }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. Grid Items Section -->
                                    <div v-if="detailsFields.length > 0" class="summary-section">
                                        <h6 class="summary-section-title text-uppercase text-success fw-bold mb-2 d-flex align-items-center gap-2" style="font-size: 0.8rem;">
                                            <i class="fas fa-list-alt"></i> Cargo & Grid Items
                                        </h6>
                                        <div v-for="field in detailsFields" :key="field.rawKey" class="table-responsive rounded border shadow-sm" style="max-height: 250px; overflow-y: auto;">
                                            <table class="table table-sm table-striped table-hover mb-0 text-nowrap" style="font-size: 0.8rem;">
                                                <thead class="bg-light sticky-top">
                                                    <tr class="border-bottom text-muted text-uppercase font-monospace" style="font-size: 0.68rem;">
                                                        <th class="ps-2 py-2">#</th>
                                                        <th v-for="col in getArrayColumns(field.value)" :key="col" class="py-2">{{ col }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(row, idx) in field.value" :key="idx" class="align-middle">
                                                        <td class="ps-2 fw-bold text-muted">{{ idx + 1 }}</td>
                                                        <td v-for="col in getArrayColumns(field.value)" :key="col" class="fw-bold text-dark">
                                                            {{ typeof row === 'object' ? row[col] : row }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Financials & Other Info -->
                                <div class="col-md-5 d-flex flex-column gap-3">
                                    <!-- 3. Financials & Totals Section -->
                                    <div class="summary-section">
                                        <h6 class="summary-section-title text-uppercase text-success fw-bold mb-2 d-flex align-items-center gap-2" style="font-size: 0.8rem;">
                                            <i class="fas fa-rupee-sign"></i> Financials & Charges
                                        </h6>
                                        <div class="row g-2">
                                            <!-- List of charges -->
                                            <div class="col-12">
                                                <div class="p-2 bg-light rounded border" style="max-height: 180px; overflow-y: auto;">
                                                    <div class="row g-1">
                                                        <template v-for="field in financialFields" :key="field.rawKey">
                                                            <div v-if="field.rawKey !== 'total_amount' && field.rawKey !== 'amount'" class="col-6 d-flex justify-content-between align-items-center py-1 px-2 border-bottom">
                                                                <span class="text-muted small fw-semibold" style="font-size: 0.75rem;">{{ field.label }}:</span>
                                                                <span class="text-dark fw-bold" style="font-size: 0.75rem;">₹{{ field.value }}</span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Big Final Total Box -->
                                            <div class="col-12">
                                                <div class="final-total-box p-2 rounded text-center d-flex flex-column justify-content-center border border-success" style="background: rgba(39, 174, 96, 0.05); border-style: dashed !important;">
                                                    <span class="text-success text-uppercase fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Total Amount</span>
                                                    <span class="text-success fw-extrabold fs-4">
                                                        ₹ {{ getFinalTotalValue() }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 4. Other Details Section (Remarks, E-way, Addon) -->
                                    <div v-if="otherFields.length > 0" class="summary-section">
                                        <h6 class="summary-section-title text-uppercase text-success fw-bold mb-2 d-flex align-items-center gap-2" style="font-size: 0.8rem;">
                                            <i class="fas fa-file-alt"></i> Other Information
                                        </h6>
                                        <div class="p-2 bg-light rounded border" style="max-height: 150px; overflow-y: auto;">
                                            <div class="row g-2">
                                                <div v-for="field in otherFields" :key="field.rawKey" class="col-12">
                                                    <div class="d-flex align-items-start gap-2">
                                                        <span class="text-muted fw-semibold small text-nowrap" style="min-width: 90px; font-size: 0.75rem;">{{ field.label }}:</span>
                                                        <span class="text-dark fw-medium small text-wrap" style="font-size: 0.75rem;">
                                                            <template v-if="field.isRawArray">
                                                                <span v-if="field.value.length === 0" class="text-muted italic">None</span>
                                                                <ul v-else class="mb-0 ps-3">
                                                                    <li v-for="(item, idx) in field.value" :key="idx" class="small">
                                                                        {{ typeof item === 'object' ? JSON.stringify(item) : item }}
                                                                    </li>
                                                                </ul>
                                                            </template>
                                                            <template v-else>
                                                                {{ field.value }}
                                                            </template>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="shortcut-modal-footer gap-2 bg-light border-top py-2 px-3">
                        <button class="btn btn-sm btn-secondary px-3 py-1-5 fw-semibold" @click="closeSummaryModal">Cancel</button>
                        <button class="btn btn-sm btn-primary btn-success px-4 py-1-5 fw-bold d-flex align-items-center gap-2" @click="executeConfirmedVisit" style="background-color: #27AE60 !important; border-color: #27AE60 !important;">
                            <i class="fas fa-check-circle"></i> Confirm & Save
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Sidebar from '@/Components/Sidebar.vue';
import Footer from '@/Components/Footer.vue';

defineProps({
    title: {
        type: String,
        default: 'Dashboard'
    }
});

const page = usePage();
const isShortcutModalOpen = ref(false);
const isSummaryModalOpen = ref(false);
const summaryFields = ref([]);
let pendingVisit = null;
let isConfirmed = false;
let unregisterBefore = null;

watch([isSummaryModalOpen, isShortcutModalOpen], ([summaryOpen, shortcutOpen]) => {
    if (summaryOpen || shortcutOpen) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

const headerKeys = [
    'bilty_number', 'challan_number', 'date', 'payment_type', 
    'from_station_id', 'to_station_id', 'consignor_id', 'consignee_id', 
    'driver_id', 'truck_id'
];

const totalKeys = [
    'freight', 'bilty_charge', 'surcharge', 'handling_charge', 
    'risk_dd_charge', 'cartage', 'cartage_dalali_pf', 'kat', 
    'labour_charge', 'home_delivery_charge', 'gst', 'paid_amount', 
    'to_pay_amount', 'less_advance_received', 'cash_received', 
    'cash_payment', 'total_amount', 'amount'
];

const headerFields = computed(() => {
    return summaryFields.value.filter(f => headerKeys.includes(f.rawKey));
});

const detailsFields = computed(() => {
    return summaryFields.value.filter(f => f.isRawArray && f.rawKey === 'details');
});

const financialFields = computed(() => {
    return summaryFields.value.filter(f => totalKeys.includes(f.rawKey));
});

const otherFields = computed(() => {
    return summaryFields.value.filter(f => 
        !headerKeys.includes(f.rawKey) && 
        !totalKeys.includes(f.rawKey) && 
        f.rawKey !== 'details'
    );
});

const getFinalTotalValue = () => {
    const totalField = summaryFields.value.find(f => f.rawKey === 'total_amount' || f.rawKey === 'amount');
    return totalField ? totalField.value : '0.00';
};

const auth = computed(() => page.props.auth || {});

const canCreateBilty = computed(() => !!(auth.value.is_admin || (auth.value.permissions && auth.value.permissions.includes('create bilties'))));
const canCreateChallan = computed(() => !!(auth.value.is_admin || (auth.value.permissions && auth.value.permissions.includes('create challans'))));
const canCreateVoucher = computed(() => !!(auth.value.is_admin || (auth.value.permissions && auth.value.permissions.includes('create vouchers'))));
const canCreateInward = computed(() => !!(auth.value.is_admin || (auth.value.permissions && auth.value.permissions.includes('create inward_registers'))));
const canCreateDelivery = computed(() => !!(auth.value.is_admin || (auth.value.permissions && auth.value.permissions.includes('create delivery_receipts'))));

const closeShortcutModal = () => {
    isShortcutModalOpen.value = false;
};

const closeSummaryModal = () => {
    isSummaryModalOpen.value = false;
    pendingVisit = null;
};

const formatKey = (key) => {
    return key
        .replace(/_id$/, '') // Remove trailing _id
        .replace(/_/g, ' ')  // Replace underscores with spaces
        .replace(/\b\w/g, c => c.toUpperCase()); // Capitalize first letters
};

const resolveIdFromProps = (key, id) => {
    if (!page.props) return null;

    const mappings = {
        station: 'stations',
        good: 'goods',
        package_type: 'package_types',
        party: 'parties',
        consignor: 'parties',
        consignee: 'parties',
        vendor: 'vendors',
        driver: 'drivers',
        truck: 'trucks',
        bilty: 'bilties',
        agent: 'agents',
        user: 'users'
    };

    const keyLower = key.toLowerCase();
    let propListName = null;
    
    for (const [sub, prop] of Object.entries(mappings)) {
        if (keyLower.includes(sub)) {
            propListName = prop;
            break;
        }
    }

    if (propListName && page.props[propListName]) {
        const list = page.props[propListName];
        if (Array.isArray(list)) {
            const item = list.find(item => String(item.id) === String(id));
            if (item) {
                return item.name || item.title || item.label || item.truck_no || item.truck_number || item.bilty_number || item.gr_no || null;
            }
        }
    }

    return null;
};

const formatValue = (key, value) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }
    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }
    if (Array.isArray(value)) {
        if (value.length === 0) return [];
        return value.map(item => {
            if (typeof item === 'object') {
                const formattedItem = {};
                for (const [k, v] of Object.entries(item)) {
                    if (!['id', 'created_at', 'updated_at', 'company_id', 'branch_id'].includes(k)) {
                        formattedItem[formatKey(k)] = formatValue(k, v);
                    }
                }
                return formattedItem;
            }
            return String(item);
        });
    }

    if (key.endsWith('_id') || key.endsWith('Id')) {
        const resolvedText = resolveIdFromProps(key, value);
        if (resolvedText) {
            return resolvedText;
        }

        const option = document.querySelector(`option[value="${value}"]`);
        if (option && option.textContent) {
            return option.textContent.trim();
        }
    }

    return String(value);
};

const getSummaryFields = (data) => {
    if (!data) return [];
    
    const ignoredKeys = [
        'id', 'password', 'password_confirmation', 'remember', 
        '_method', 'created_at', 'updated_at', 'csrf_token', 'company_id', 'branch_id'
    ];

    return Object.entries(data)
        .filter(([key, val]) => !ignoredKeys.includes(key))
        .map(([key, val]) => {
            return {
                rawKey: key,
                label: formatKey(key),
                value: formatValue(key, val),
                isRawArray: Array.isArray(val)
            };
        });
};

const getArrayColumns = (arrayVal) => {
    if (!Array.isArray(arrayVal) || arrayVal.length === 0) return [];
    const firstRow = arrayVal[0];
    if (typeof firstRow === 'object') {
        return Object.keys(firstRow);
    }
    return ['Item'];
};

const executeConfirmedVisit = () => {
    if (pendingVisit) {
        isConfirmed = true;
        isSummaryModalOpen.value = false;
        
        router.visit(pendingVisit.url, {
            method: pendingVisit.method,
            data: pendingVisit.data,
            headers: pendingVisit.headers,
            preserveScroll: pendingVisit.preserveScroll,
            preserveState: pendingVisit.preserveState,
            onFinish: () => {
                isConfirmed = false;
                pendingVisit = null;
            }
        });
    }
};

const navigateTo = (url) => {
    closeShortcutModal();
    const currentPath = page.url.split('?')[0];
    if (currentPath !== url) {
        router.visit(url);
    }
};

const handleEnterAsTab = (event) => {
    if (event.key !== 'Enter') return;

    const activeEl = document.activeElement;
    if (!activeEl) return;

    const tagName = activeEl.tagName.toLowerCase();
    const type = activeEl.type ? activeEl.type.toLowerCase() : '';

    // Ignore textareas, buttons, anchor tags, and standard submit/button inputs
    if (tagName === 'textarea') return;
    if (tagName === 'button') return;
    if (tagName === 'a') return;
    if (type === 'submit' || type === 'button' || type === 'reset' || type === 'image') return;

    // Prevent default form submission
    event.preventDefault();

    // Check if we are inside a table row of a dynamic grid
    const tr = activeEl.closest('tr') || activeEl.closest('.dynamic-row');
    const table = tr ? (tr.closest('table') || tr.closest('.dynamic-grid')) : null;
    
    if (tr && table) {
        const rowInputs = Array.from(
            tr.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled])')
        ).filter(el => {
            const style = window.getComputedStyle(el);
            return style.display !== 'none' && style.visibility !== 'hidden';
        });

        const isLastInputInRow = rowInputs.indexOf(activeEl) === rowInputs.length - 1;

        if (isLastInputInRow) {
            // Find all rows in the table/grid that contain inputs
            const allRowsWithInputs = Array.from(table.querySelectorAll('tr, .dynamic-row')).filter(row => {
                return row.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled])').length > 0;
            });
            const isLastRow = allRowsWithInputs.indexOf(tr) === allRowsWithInputs.length - 1;

            if (isLastRow) {
                // Find "Add Row" button in the same table/grid
                const buttons = Array.from(table.querySelectorAll('button, a'));
                const addRowBtn = buttons.find(btn => {
                    const text = btn.textContent.toLowerCase();
                    return text.includes('add row') || text.includes('add bilty') || text.includes('add e-way') || text.includes('add eway') || text.includes('add addon') || text.includes('add charge') || text.trim() === 'add';
                });

                if (addRowBtn) {
                    // Prompt user
                    if (confirm('Do you want to add more details?')) {
                        addRowBtn.click();
                        
                        // Wait for Vue to render the new row, then focus its first input
                        setTimeout(() => {
                            const updatedRows = Array.from(table.querySelectorAll('tr, .dynamic-row')).filter(row => {
                                return row.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled])').length > 0;
                            });
                            if (updatedRows.length > 0) {
                                const newLastRow = updatedRows[updatedRows.length - 1];
                                const newRowInputs = Array.from(
                                    newLastRow.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled])')
                                ).filter(el => {
                                    const style = window.getComputedStyle(el);
                                    return style.display !== 'none' && style.visibility !== 'hidden';
                                });
                                
                                if (newRowInputs.length > 0) {
                                    const firstInput = newRowInputs[0];
                                    firstInput.focus();
                                }
                            }
                        }, 100);
                        return;
                    } else {
                        // User chose "No" -> Focus the first element AFTER the table/grid
                        const form = activeEl.closest('form') || document.body;
                        const allFocusable = Array.from(
                            form.querySelectorAll(
                                'input:not([type="hidden"]):not([disabled]):not([type="button"]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]):not([type="button"]), [tabindex="0"]'
                            )
                        ).filter((el) => {
                            if (el.tabIndex < 0 || el.getAttribute('tabindex') === '-1') return false;
                            const style = window.getComputedStyle(el);
                            return style.display !== 'none' && style.visibility !== 'hidden' && el.offsetWidth > 0 && el.offsetHeight > 0;
                        });

                        const lastTableInput = rowInputs[rowInputs.length - 1];
                        const lastIndex = allFocusable.indexOf(lastTableInput);
                        
                        const nextElement = allFocusable.slice(lastIndex + 1).find(el => !table.contains(el));
                        if (nextElement) {
                            nextElement.focus();
                            return;
                        }
                    }
                }
            }
        }
    }

    // Default Enter-as-Tab focus progression if not intercepted above
    const container = activeEl.closest('form') || document.body;
    const focusableElements = Array.from(
        container.querySelectorAll(
            'input:not([type="hidden"]):not([disabled]):not([type="button"]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]):not([type="button"]), [tabindex="0"]'
        )
    ).filter((el) => {
        if (el.tabIndex < 0 || el.getAttribute('tabindex') === '-1') return false;
        const style = window.getComputedStyle(el);
        return style.display !== 'none' && style.visibility !== 'hidden' && el.offsetWidth > 0 && el.offsetHeight > 0;
    });

    const index = focusableElements.indexOf(activeEl);
    if (index > -1 && index < focusableElements.length - 1) {
        focusableElements[index + 1].focus();
    }
};

const handleKeyDown = (event) => {
    // ESC to close modal
    if (event.key === 'Escape') {
        if (isShortcutModalOpen.value) {
            event.preventDefault();
            closeShortcutModal();
            return;
        }
        if (isSummaryModalOpen.value) {
            event.preventDefault();
            closeSummaryModal();
            return;
        }
    }

    // F3 -> toggle shortcut modal
    if (event.key === 'F3') {
        event.preventDefault();
        isShortcutModalOpen.value = !isShortcutModalOpen.value;
        return;
    }

    // F5 -> inward registers create
    if (event.key === 'F5') {
        event.preventDefault();
        if (canCreateInward.value) {
            navigateTo('/inward-registers/create');
        }
        return;
    }

    // F6 -> delivery receipts create
    if (event.key === 'F6') {
        event.preventDefault();
        if (canCreateDelivery.value) {
            navigateTo('/delivery-receipts/create');
        }
        return;
    }

    // F7 -> challan create
    if (event.key === 'F7') {
        event.preventDefault();
        if (canCreateChallan.value) {
            navigateTo('/challans/create');
        }
        return;
    }

    // F8 -> main challan create
    if (event.key === 'F8') {
        event.preventDefault();
        if (canCreateChallan.value) {
            navigateTo('/main-challans/create');
        }
        return;
    }

    // F9 -> bilty create
    if (event.key === 'F9') {
        event.preventDefault();
        if (canCreateBilty.value) {
            navigateTo('/bilties/create');
        }
        return;
    }

    // F10 -> voucher create
    if (event.key === 'F10') {
        event.preventDefault();
        if (canCreateVoucher.value) {
            navigateTo('/vouchers/create');
        }
        return;
    }
};

onMounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    window.addEventListener('keydown', handleKeyDown);

    window.removeEventListener('keydown', handleEnterAsTab);
    window.addEventListener('keydown', handleEnterAsTab);

    unregisterBefore = router.on('before', (event) => {
        const method = event.detail.visit.method.toLowerCase();
        if (['post', 'put', 'patch'].includes(method)) {
            if (isConfirmed) {
                isConfirmed = false;
                return;
            }

            const url = event.detail.visit.url;
            const pathname = typeof url === 'string' ? new URL(url, window.location.origin).pathname : url.pathname;

            if (pathname.endsWith('/logout')) {
                return;
            }

            const fields = getSummaryFields(event.detail.visit.data);
            if (fields.length === 0) {
                return;
            }

            // Intercept
            event.preventDefault();
            pendingVisit = event.detail.visit;
            summaryFields.value = fields;
            isSummaryModalOpen.value = true;
        }
    });
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('keydown', handleEnterAsTab);
    if (unregisterBefore) {
        unregisterBefore();
    }
});
</script>

<style>
.app-wrapper {
    height: 100vh;
    overflow: hidden;
}

.app-main {
    transition: margin-left 0.3s;
    overflow-y: auto;
    overflow-x: hidden;
}

/* Glassmorphism Shortcut Modal Styles */
.shortcut-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.45); /* Elegant dark slate backdrop */
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1060; /* Higher than sidebar / navbar */
}

.shortcut-modal-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04), inset 0 1px 0 0 rgba(255, 255, 255, 0.6);
    border-radius: 20px;
    width: 90%;
    max-width: 500px;
    overflow: hidden;
    animation: scaleUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes scaleUp {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.shortcut-modal-header {
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.btn-close-custom {
    background: none;
    border: none;
    color: #64748b;
    font-size: 1.1rem;
    cursor: pointer;
    transition: color 0.2s, transform 0.2s;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.btn-close-custom:hover {
    color: #0f172a;
    background-color: rgba(0, 0, 0, 0.05);
    transform: rotate(90deg);
}

.shortcut-modal-body {
    padding: 24px;
}

.shortcut-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.shortcut-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    padding: 12px 16px;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.05);
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    text-align: left;
}

.shortcut-item:hover {
    background: rgba(255, 255, 255, 0.95);
    border-color: #27AE60;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.shortcut-item-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.icon-frame {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(0, 0, 0, 0.03);
    font-size: 1.1rem;
}

.shortcut-item:hover .icon-frame {
    background: rgba(39, 174, 96, 0.1);
}

.shortcut-item-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.95rem;
}

.shortcut-item-badge {
    background: #f1f5f9;
    color: #475569;
    font-weight: 700;
    font-size: 0.8rem;
    padding: 4px 10px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    min-width: 44px;
    text-align: center;
}

.shortcut-item:hover .shortcut-item-badge {
    background: #27AE60;
    color: white;
    border-color: #27AE60;
}

.shortcut-modal-footer {
    padding: 16px 24px;
    background: rgba(0, 0, 0, 0.02);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    display: flex;
    justify-content: flex-end;
}

/* Vue Transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Summary Modal Styles */
.summary-modal-card {
    max-width: 950px;
    width: 95%;
    max-height: 95vh;
    display: flex;
    flex-direction: column;
}

.summary-modal-body {
    overflow-y: auto;
    max-height: 80vh;
}

/* Styled scrollbars for a premium feel */
.summary-modal-body::-webkit-scrollbar,
.table-responsive::-webkit-scrollbar,
.summary-modal-body *::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.summary-modal-body::-webkit-scrollbar-track,
.table-responsive::-webkit-scrollbar-track,
.summary-modal-body *::-webkit-scrollbar-track {
    background: transparent;
}

.summary-modal-body::-webkit-scrollbar-thumb,
.table-responsive::-webkit-scrollbar-thumb,
.summary-modal-body *::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.summary-modal-body::-webkit-scrollbar-thumb:hover,
.table-responsive::-webkit-scrollbar-thumb:hover,
.summary-modal-body *::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.summary-section-title {
    font-size: 0.85rem;
    border-left: 4px solid var(--color-brand-primary, #27AE60);
    padding-left: 8px;
    letter-spacing: 0.05em;
}

.header-info-box {
    background: rgba(248, 250, 252, 0.8);
    border-color: #e2e8f0 !important;
}

.final-total-box {
    min-height: 100px;
}

.fw-extrabold {
    font-weight: 800;
}
</style>
