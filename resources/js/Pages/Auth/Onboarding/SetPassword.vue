<template>
    <div class="onboarding-page">
        <div class="onboarding-box">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <img src="/logo.png" alt="TruckBill Logo" class="mb-3" style="max-height: 80px;">
                        <h5 class="fw-bold text-dark mb-2">Set your password</h5>
                        <p class="text-muted small mb-0">
                            Create a secure password for {{ user.email }}
                        </p>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="mb-4 position-relative">
                            <label class="form-label fw-bold text-muted small">Password</label>
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                class="form-control form-control-lg"
                                v-model="form.password"
                                required
                                minlength="8"
                            >
                            <button
                                type="button"
                                class="btn btn-link position-absolute end-0 top-50 mt-2 me-2 text-muted"
                                @click="showPassword = !showPassword"
                            >
                                <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                            <div v-if="form.errors.password" class="text-danger small mt-1">{{ form.errors.password }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small">Confirm Password</label>
                            <input
                                type="password"
                                class="form-control form-control-lg"
                                v-model="form.password_confirmation"
                                required
                                minlength="8"
                            >
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary btn-lg w-100 py-3 fw-bold rounded-3"
                            :disabled="form.processing"
                            style="background-color: #27AE60; border: none;"
                        >
                            Complete Setup
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    submit_url: String,
});

const showPassword = ref(false);

const form = useForm({
    password: '',
    password_confirmation: '',
});

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
    max-width: 480px;
}
</style>
