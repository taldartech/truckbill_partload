<template>
    <div class="auth-page">
        <div class="auth-box">
            <div class="card auth-card shadow-lg border-0">
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

                        <p class="text-muted small mb-4">
                            Use at least 8 characters with uppercase and lowercase letters, a number, and a symbol (e.g. ! @ # $).
                        </p>

                        <button
                            type="submit"
                            class="btn btn-primary btn-lg w-100 py-3 fw-bold rounded-3"
                            :disabled="form.processing"
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
.auth-page {
    padding: 1rem;
}
</style>
