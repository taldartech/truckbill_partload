import { computed, ref } from 'vue';
import { closeAllSearchableSelects } from '@/utils/searchableSelectRegistry';

const activeField = ref(null);
const openSession = ref(null);
const sessionStack = ref([]);
const isOpen = ref(false);
const openRequestId = ref(0);

export const MASTER_PERMISSIONS = {
    party: 'create parties',
    station: 'create stations',
    agent: 'create agents',
    vendor: 'create vendors',
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

    const openQuickAdd = () => {
        const field = activeField.value;

        if (!field?.master || !field.canCreate) {
            return false;
        }

        // Nested quick-add is disabled while a modal is already open
        if (isOpen.value) {
            return false;
        }

        // Dropdowns teleport above Bootstrap's default modal z-index — close them first.
        closeAllSearchableSelects();

        openSession.value = { ...field };
        isOpen.value = true;
        openRequestId.value += 1;

        return true;
    };

    const closeQuickAdd = () => {
        openSession.value = null;
        isOpen.value = false;
        sessionStack.value = [];
    };

    const notifyCreated = (record) => {
        const session = openSession.value;

        if (session?.onCreated) {
            session.onCreated(record);
        }

        openSession.value = null;
        isOpen.value = false;
        sessionStack.value = [];

        if (session?.focusTrigger) {
            session.focusTrigger();
        }
    };

    return {
        activeField,
        openSession,
        sessionStack,
        activeMaster,
        activeContext,
        isOpen,
        openRequestId,
        canOpen,
        register,
        unregister,
        openQuickAdd,
        closeQuickAdd,
        notifyCreated,
    };
}
