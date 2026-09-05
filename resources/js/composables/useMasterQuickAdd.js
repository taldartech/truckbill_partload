import { computed, ref } from 'vue';
import { closeAllSearchableSelects } from '@/utils/searchableSelectRegistry';

const activeField = ref(null);
const openSession = ref(null);
const sessionStack = ref([]);
const isOpen = ref(false);
const openRequestId = ref(0);

const MAX_NESTING_DEPTH = 3;

export const MASTER_PERMISSIONS = {
    party: 'create parties',
    station: 'create stations',
    agent: 'create agents',
    vendor: 'create vendors',
    truck_owner: 'create truck_owners',
    good: 'create goods',
    package_type: 'create package_types',
    truck: 'create trucks',
    truck_type: 'create truck_types',
    group: 'create groups',
    country: null,
    state: null,
    city: null,
};

export const MASTER_TITLES = {
    party: 'Party',
    station: 'Station',
    agent: 'Agent',
    vendor: 'Vendor',
    truck_owner: 'Truck Owner',
    good: 'Good',
    package_type: 'Package Type',
    truck: 'Truck',
    truck_type: 'Truck Type',
    group: 'Group',
    country: 'Country',
    state: 'State',
    city: 'City',
};

/**
 * @param {import('vue').Ref<Array> | Array} options
 * @param {Record<string, any>} record
 * @param {string} valueKey
 */
export function appendMasterOption(options, record, valueKey = 'id') {
    if (!record || record[valueKey] === undefined || record[valueKey] === null) {
        return;
    }

    const list = Array.isArray(options) ? options : options?.value;

    if (!Array.isArray(list)) {
        return;
    }

    const exists = list.some((item) => String(item[valueKey]) === String(record[valueKey]));

    if (!exists) {
        list.push(record);
    }
}

export function canCreateMaster(master, auth = {}) {
    if (!master) {
        return false;
    }

    const permission = MASTER_PERMISSIONS[master];

    if (permission === undefined) {
        return false;
    }

    if (permission === null) {
        return true;
    }

    if (auth.is_admin) {
        return true;
    }

    return Array.isArray(auth.permissions) && auth.permissions.includes(permission);
}

export function useMasterQuickAdd() {
    const activeMaster = computed(() => openSession.value?.master ?? activeField.value?.master ?? null);
    const activeContext = computed(() => openSession.value?.context ?? activeField.value?.context ?? {});
    const nestingDepth = computed(() => sessionStack.value.length);
    const canOpen = computed(() => {
        const field = activeField.value;

        if (!field?.master || !field.canCreate) {
            return false;
        }

        return true;
    });

    const register = (field) => {
        if (!field?.master) {
            return;
        }

        activeField.value = {
            master: field.master,
            token: field.token ?? null,
            onCreated: field.onCreated,
            canCreate: field.canCreate !== false,
            context: field.context || {},
            focusTrigger: field.focusTrigger || null,
        };
    };

    const unregister = (field) => {
        if (!activeField.value) {
            return;
        }

        if (field?.token && activeField.value.token) {
            if (field.token !== activeField.value.token) {
                return;
            }
        } else if (field?.master && activeField.value.master !== field.master) {
            return;
        }

        activeField.value = null;
    };

    /** Clear F3 target when focus moves to a field that cannot quick-add. */
    const clearActive = () => {
        activeField.value = null;
    };

    const openQuickAdd = () => {
        const field = activeField.value;

        if (!field?.master || !field.canCreate) {
            return false;
        }

        // Avoid opening the same master on top of itself
        if (isOpen.value && openSession.value?.master === field.master) {
            return false;
        }

        if (isOpen.value && sessionStack.value.length >= MAX_NESTING_DEPTH) {
            return false;
        }

        // Dropdowns teleport above Bootstrap's default modal z-index — close them first.
        closeAllSearchableSelects();

        if (isOpen.value && openSession.value) {
            sessionStack.value.push(openSession.value);
        }

        openSession.value = { ...field };
        isOpen.value = true;
        openRequestId.value += 1;

        return true;
    };

    const closeQuickAdd = () => {
        const previous = sessionStack.value.pop();

        if (previous) {
            openSession.value = previous;
            openRequestId.value += 1;
            return true;
        }

        openSession.value = null;
        isOpen.value = false;
        return false;
    };

    const closeAllQuickAdd = () => {
        sessionStack.value = [];
        openSession.value = null;
        isOpen.value = false;
    };

    const notifyCreated = (record) => {
        const session = openSession.value;
        const previous = sessionStack.value.pop();

        if (session?.onCreated) {
            session.onCreated(record);
        }

        if (previous) {
            openSession.value = previous;
            openRequestId.value += 1;

            return true;
        }

        openSession.value = null;
        isOpen.value = false;

        if (session?.focusTrigger) {
            session.focusTrigger();
        }

        return false;
    };

    return {
        activeField,
        openSession,
        sessionStack,
        activeMaster,
        activeContext,
        nestingDepth,
        isOpen,
        openRequestId,
        canOpen,
        register,
        unregister,
        clearActive,
        openQuickAdd,
        closeQuickAdd,
        closeAllQuickAdd,
        notifyCreated,
    };
}
