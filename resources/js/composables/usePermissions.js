import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();
    const auth = computed(() => page.props.auth || {});

    const permissions = computed(() => auth.value.effective_permissions || auth.value.permissions || []);

    const can = (permission) => permissions.value.includes(permission);

    const isMasterAdmin = computed(() => !!auth.value.is_master_admin);
    const isSuperAdmin = computed(() => !!auth.value.is_super_admin);
    const isActingAsAdmin = computed(() => !!auth.value.is_acting_as_admin);

    const showPortalMenu = computed(() => isMasterAdmin.value);

    const showSetupMenu = computed(() => {
        if (isMasterAdmin.value) {
            return false;
        }

        return isSuperAdmin.value && !isActingAsAdmin.value;
    });

    const showOperationsMenu = computed(() => {
        if (isMasterAdmin.value) {
            return false;
        }

        if (isSuperAdmin.value) {
            return isActingAsAdmin.value;
        }

        return true;
    });

    const canManageCompanyContext = computed(() => isSuperAdmin.value || auth.value.is_admin);

    return {
        auth,
        permissions,
        can,
        isMasterAdmin,
        isSuperAdmin,
        isActingAsAdmin,
        showPortalMenu,
        showSetupMenu,
        showOperationsMenu,
        canManageCompanyContext,
    };
}
