<template>
    <div class="auth-page">
        <div class="auth-box">
            <div class="card auth-card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="auth-logo-section">
                        <img src="/logo.png" alt="TruckBill Logo" class="auth-brand-image">
                    </div>
                    <h5 class="auth-page-title">Welcome back! Please sign in to continue.</h5>

                    <div v-if="$page.props.flash?.error" class="alert alert-danger small">
                        {{ $page.props.flash.error }}
                    </div>
                    <div v-if="$page.props.flash?.success" class="alert alert-success small">
                        {{ $page.props.flash.success }}
                    </div>

                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control form-control-lg"
                                   placeholder="name@company.com" v-model="form.email" required>
                            <div v-if="form.errors.email" class="text-danger small mt-1">{{ form.errors.email }}</div>
                        </div>
                        
                        <PasswordInput
                            v-model="form.password"
                            label="Password"
                            placeholder="Enter your password"
                            wrapper-class="mb-4"
                            input-class="form-control-lg"
                            autocomplete="current-password"
                            :error="form.errors.password"
                            required
                        />

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" v-model="form.remember" id="remember">
                                <label class="form-check-label text-muted small" for="remember">
                                    Remember me
                                </label>
                            </div>
                            <a href="#" class="text-brand-primary fw-semibold small text-decoration-none">Forgot password?</a>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-semibold rounded-3 shadow-sm d-flex align-items-center justify-content-center"
                                :disabled="form.processing">
                            Sign In <i class="fas fa-arrow-right ms-2"></i>
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

const form = useForm({
    email: '',
    password: '',
    remember: false
});

const submit = () => {
    form.post('/login');
};
</script>
