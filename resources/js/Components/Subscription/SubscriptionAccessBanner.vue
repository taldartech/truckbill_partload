<template>
    <div
        v-if="subscription && ['grace', 'locked'].includes(subscription.access_mode)"
        class="alert alert-warning alert-dismissible fade show"
        role="alert"
    >
        <template v-if="subscription.access_mode === 'grace'">
            Your subscription has expired.
            <span v-if="subscription.grace_ends_at">
                You have view-only access until {{ formattedGraceEnd }}.
            </span>
            <span v-else>You have view-only access during the grace period.</span>
            Please renew to restore full access.
        </template>
        <template v-else>
            Your subscription has expired. Please renew to continue using the application.
        </template>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const subscription = computed(() => usePage().props.auth?.subscription ?? null);

const formattedGraceEnd = computed(() => {
    if (!subscription.value?.grace_ends_at) {
        return '';
    }

    return new Date(subscription.value.grace_ends_at).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
});
</script>
