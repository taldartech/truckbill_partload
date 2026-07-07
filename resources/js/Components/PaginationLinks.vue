<template>
    <div
        v-if="pagination?.links?.length > 3"
        class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2"
    >
        <div class="text-muted small">
            Showing {{ pagination.from ?? 0 }} to {{ pagination.to ?? 0 }} of {{ pagination.total ?? 0 }}
        </div>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li
                    v-for="link in pagination.links"
                    :key="link.label"
                    class="page-item"
                    :class="{ active: link.active, disabled: !link.url }"
                >
                    <Link
                        v-if="link.url"
                        class="page-link"
                        :href="link.url"
                        preserve-scroll
                        v-html="link.label"
                    />
                    <span v-else class="page-link" v-html="link.label" />
                </li>
            </ul>
        </nav>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    pagination: {
        type: Object,
        required: true,
    },
});
</script>
