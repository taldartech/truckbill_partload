<template>
    <div class="onboarding-page">
        <div class="onboarding-box">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <img src="/logo.png" alt="TruckBill Logo" class="mb-3" style="max-height: 80px;">
                        <h5 class="fw-bold text-dark mb-2">Accept policies to continue</h5>
                        <p class="text-muted small mb-0">
                            Please review and accept all TruckBill policies before using the portal.
                        </p>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="policy-list mb-4">
                            <div
                                v-for="policy in policies"
                                :key="policy.key"
                                class="form-check mb-3 p-3 border rounded bg-light"
                            >
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    :id="policy.key"
                                    v-model="form.policies[policy.key]"
                                >
                                <label class="form-check-label ms-1" :for="policy.key">
                                    I accept the
                                    <a :href="policy.url" target="_blank" rel="noopener noreferrer" class="fw-bold">
                                        {{ policy.label }}
                                    </a>
                                </label>
                                <div v-if="form.errors[`policies.${policy.key}`]" class="text-danger small mt-1">
                                    {{ form.errors[`policies.${policy.key}`] }}
                                </div>
                            </div>
                        </div>

                        <p class="text-muted small mb-3">
                            Policy version: {{ policy_version }}
                        </p>

                        <button
                            type="submit"
                            class="btn btn-primary btn-lg w-100 py-3 fw-bold rounded-3"
                            :disabled="form.processing || !allAccepted"
                            style="background-color: #27AE60; border: none;"
                        >
                            Continue
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    policies: Array,
    policy_version: String,
    submit_url: String,
});

const initialPolicies = props.policies.reduce((carry, policy) => {
    carry[policy.key] = false;
    return carry;
}, {});

const form = useForm({
    policies: initialPolicies,
});

const allAccepted = computed(() =>
    props.policies.every((policy) => form.policies[policy.key])
);

const submit = () => {
    form.post(props.submit_url);
};
</script>

<style scoped>
.onboarding-page {
    min-height: 100vh;
    background-color: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.onboarding-box {
    width: 100%;
    max-width: 560px;
}
</style>
