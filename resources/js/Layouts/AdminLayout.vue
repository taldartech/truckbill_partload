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
                    <div v-if="$page.props.flash?.success" class="alert alert-success">{{ $page.props.flash.success }}</div>
                    <div v-if="$page.props.flash?.error" class="alert alert-danger">{{ $page.props.flash.error }}</div>
                    <SubscriptionAccessBanner />
                    <slot />
                </div>
            </div>
        </main>
        <Footer />

        <!-- Global Shortcuts Modal (F1) -->
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
                            <button
                                class="shortcut-item"
                                @click="closeShortcutModal"
                            >
                                <span class="shortcut-item-info">
                                    <i class="fas fa-plus-circle text-success icon-frame"></i>
                                    <span class="shortcut-item-name">Quick add focused master</span>
                                </span>
                                <span class="shortcut-item-badge">F3</span>
                            </button>
                        </div>
                    </div>
                    <div class="shortcut-modal-footer">
                        <span class="text-muted small"><kbd class="bg-dark text-white px-2 py-1 rounded">ESC</kbd> to close</span>
                    </div>
                </div>
            </div>
        </Transition>

        <KeyboardConfirmModal />
        <MasterQuickAddHost />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Sidebar from '@/Components/Sidebar.vue';
import Footer from '@/Components/Footer.vue';
import SubscriptionAccessBanner from '@/Components/Subscription/SubscriptionAccessBanner.vue';
import KeyboardConfirmModal from '@/Components/KeyboardConfirmModal.vue';
import MasterQuickAddHost from '@/Components/MasterQuickAdd/MasterQuickAddHost.vue';
import { askKeyboardConfirm, isKeyboardConfirmOpen } from '@/composables/useKeyboardConfirm';
import { canCreateMaster, useMasterQuickAdd } from '@/composables/useMasterQuickAdd';
import {
    findAddRowButton,
    getRowsWithInputs,
    getVisibleRowInputs,
    resolveAddMoreMessage,
} from '@/utils/dynamicGrid';

defineProps({
    title: {
        type: String,
        default: 'Dashboard'
    }
});

const page = usePage();
const isShortcutModalOpen = ref(false);
const { openQuickAdd, activeField, isOpen: isMasterQuickAddOpen } = useMasterQuickAdd();

watch(isShortcutModalOpen, (shortcutOpen) => {
    document.body.style.overflow = shortcutOpen ? 'hidden' : '';
});

const auth = computed(() => page.props.auth || {});

const canCreateBilty = computed(() => !!(auth.value.is_admin || (auth.value.permissions && auth.value.permissions.includes('create bilties'))));
const canCreateChallan = computed(() => !!(auth.value.is_admin || (auth.value.permissions && auth.value.permissions.includes('create challans'))));
const canCreateVoucher = computed(() => !!(auth.value.is_admin || (auth.value.permissions && auth.value.permissions.includes('create vouchers'))));
const canCreateInward = computed(() => !!(auth.value.is_admin || (auth.value.permissions && auth.value.permissions.includes('create inward_registers'))));
const canCreateDelivery = computed(() => !!(auth.value.is_admin || (auth.value.permissions && auth.value.permissions.includes('create delivery_receipts'))));

const tryOpenMasterQuickAdd = () => {
    const field = activeField.value;

    if (!field?.master) {
        return false;
    }

    if (!canCreateMaster(field.master, auth.value) || field.canCreate === false) {
        return false;
    }

    return openQuickAdd();
};

const closeShortcutModal = () => {
    isShortcutModalOpen.value = false;
};

const navigateTo = (url) => {
    closeShortcutModal();
    const currentPath = page.url.split('?')[0];
    if (currentPath !== url) {
        router.visit(url);
    }
};

const promptAddDynamicRow = async (container, addBtn, activeEl) => {
    const message = resolveAddMoreMessage(container, addBtn, activeEl);
    const yes = await askKeyboardConfirm(message, {
        from: activeEl,
        skipContainer: container,
    });
    if (yes) {
        addBtn.click();
        setTimeout(() => focusFirstInputInLastRow(container), 100);
        return true;
    }

    return true;
};

const focusFirstInputInLastRow = (container) => {
    const rows = getRowsWithInputs(container);
    if (rows.length === 0) return;
    const inputs = getVisibleRowInputs(rows[rows.length - 1]);
    if (inputs.length > 0) inputs[0].focus();
};

const handleEnterAsTab = async (event) => {
    if (event.key !== 'Enter' || isKeyboardConfirmOpen.value) return;

    const activeEl = document.activeElement;
    if (!activeEl) return;

    // Leave Enter handling to SearchableSelect when its dropdown search/list is focused
    if (activeEl.closest?.('.searchable-select__dropdown')) {
        return;
    }

    const tagName = activeEl.tagName.toLowerCase();
    const type = activeEl.type ? activeEl.type.toLowerCase() : '';

    // Ignore textareas, buttons, anchor tags, and standard submit/button inputs
    if (tagName === 'textarea') return;
    if (tagName === 'button') return;
    if (tagName === 'a') return;
    if (type === 'submit' || type === 'button' || type === 'reset' || type === 'image') return;

    // Prevent default form submission
    event.preventDefault();
    event.stopPropagation();

    // Prompt to add first row when entering an empty e-way / addon section
    const gridTrigger = activeEl.getAttribute('data-dynamic-grid-trigger');
    if (gridTrigger) {
        const form = activeEl.closest('form') || document;
        const grid = form.querySelector(`[data-dynamic-grid="${gridTrigger}"]`);
        if (grid && getRowsWithInputs(grid).length === 0) {
            const addBtn = findAddRowButton(grid);
            if (addBtn) {
                await promptAddDynamicRow(grid, addBtn, activeEl);
                return;
            }
        }
    }

    // Check if we are inside a table row or dynamic-grid row
    const tr = activeEl.closest('tr') || activeEl.closest('.dynamic-row');
    const table = tr ? (tr.closest('table') || tr.closest('.dynamic-grid')) : null;

    if (tr && table) {
        const rowInputs = getVisibleRowInputs(tr);
        const isLastInputInRow = rowInputs.indexOf(activeEl) === rowInputs.length - 1;

        if (isLastInputInRow) {
            const allRowsWithInputs = getRowsWithInputs(table);
            const isLastRow = allRowsWithInputs.indexOf(tr) === allRowsWithInputs.length - 1;

            if (isLastRow) {
                const addRowBtn = findAddRowButton(table, tr);
                if (addRowBtn) {
                    await promptAddDynamicRow(table, addRowBtn, activeEl);
                    return;
                }
            }
        }
    }

    // Default Enter-as-Tab focus progression if not intercepted above
    const container = activeEl.closest('form') || document.body;
    const focusableElements = Array.from(
        container.querySelectorAll(
            [
                'input:not([type="hidden"]):not([disabled]):not([type="button"]):not([type="submit"]):not([type="reset"])',
                'select:not([disabled])',
                'textarea:not([disabled])',
                '.searchable-select__trigger:not([disabled])',
            ].join(', ')
        )
    ).filter((el) => {
        if (el.tabIndex < 0 || el.getAttribute('tabindex') === '-1') return false;
        if (el.closest?.('.searchable-select__dropdown')) return false;
        const style = window.getComputedStyle(el);
        return style.display !== 'none' && style.visibility !== 'hidden' && el.offsetWidth > 0 && el.offsetHeight > 0;
    });

    const index = focusableElements.indexOf(activeEl);
    if (index > -1 && index < focusableElements.length - 1) {
        focusableElements[index + 1].focus();
    }
};

const handleKeyDown = (event) => {
    if (isKeyboardConfirmOpen.value) return;

    // ESC to close modal
    if (event.key === 'Escape') {
        if (isShortcutModalOpen.value) {
            event.preventDefault();
            closeShortcutModal();
        }
        return;
    }

    // F1 -> toggle shortcut modal
    if (event.key === 'F1') {
        event.preventDefault();
        isShortcutModalOpen.value = !isShortcutModalOpen.value;
        return;
    }

    // F3 -> quick add for focused master select (disabled while quick-add modal is open)
    if (event.key === 'F3') {
        event.preventDefault();
        if (!isMasterQuickAddOpen.value) {
            tryOpenMasterQuickAdd();
        }
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
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('keydown', handleEnterAsTab);
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
</style>
