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
                        <PasswordInput
                            v-model="form.password"
                            label="Password"
                            wrapper-class="mb-4"
                            input-class="form-control-lg"
                            autocomplete="new-password"
                            :error="form.errors.password"
                            :minlength="8"
                            required
                        />

                        <PasswordInput
                            v-model="form.password_confirmation"
                            label="Confirm Password"
                            wrapper-class="mb-4"
                            input-class="form-control-lg"
                            autocomplete="new-password"
                            :minlength="8"
                            required
                        />

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
import { useForm } from '@inertiajs/vue3';
import PasswordInput from '@/Components/PasswordInput.vue';

const props = defineProps({
    user: Object,
    submit_url: String,
});

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
