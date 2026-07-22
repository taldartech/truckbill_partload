<template>
    <div class="row app-form-page" :class="{ 'app-form-page--compact': compact }">
        <div :class="columnClass">
            <div class="card app-form-card" :class="{ 'app-form-card--compact': compact }">
                <div class="card-header">
                    <h3 class="card-title mb-0">{{ title }}</h3>
                </div>
                <form @submit.prevent="$emit('submit')" :enctype="enctype">
                    <div class="card-body">
                        <slot />
                    </div>
                    <div class="card-footer app-form-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <slot name="footer">
                            <Link v-if="cancelHref" :href="cancelHref" class="btn btn-secondary">
                                {{ cancelLabel }}
                            </Link>
                            <span v-else />
                            <button type="submit" class="btn" :class="submitClass" :disabled="processing">
                                <i v-if="submitIcon" :class="submitIcon" class="me-1" />
                                {{ processing ? processingLabel : submitLabel }}
                            </button>
                        </slot>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, required: true },
    cancelHref: { type: String, default: '' },
    cancelLabel: { type: String, default: 'Cancel' },
    submitLabel: { type: String, default: 'Save' },
    processingLabel: { type: String, default: 'Saving…' },
    submitIcon: { type: String, default: 'fas fa-save' },
    submitClass: { type: String, default: 'btn-primary' },
    processing: { type: Boolean, default: false },
    enctype: { type: String, default: undefined },
    columnClass: { type: String, default: 'col-xl-10 col-lg-11 mx-auto' },
    compact: { type: Boolean, default: false },
});

defineEmits(['submit']);
</script>
