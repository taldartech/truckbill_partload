<template>
    <div class="onboarding-page">
        <div class="onboarding-box">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <img src="/logo.png" alt="TruckBill Logo" class="mb-3" style="max-height: 80px;">
                        <h5 class="fw-bold text-dark mb-2">Verify your email</h5>
                        <p class="text-muted small mb-0">
                            Hello {{ user.name }}, verify <strong>{{ user.email }}</strong> to continue onboarding.
                        </p>
                    </div>

                    <div v-if="$page.props.flash?.success" class="alert alert-success small">
                        {{ $page.props.flash.success }}
                    </div>

                    <div v-if="!canEnterOtp" class="text-center">
                        <p class="text-muted small mb-4">
                            Click below to receive a one-time password at your email address.
                        </p>
                        <button
                            type="button"
                            class="btn btn-primary btn-lg w-100 py-3 fw-bold rounded-3"
                            @click="sendOtp"
                            :disabled="sendingOtp"
                            style="background-color: #27AE60; border: none;"
                        >
                            Send OTP to Email
                        </button>
                    </div>

                    <form v-else @submit.prevent="submit">
                        <p class="text-muted small mb-3">
                            Enter the OTP sent to <strong>{{ user.email }}</strong>
                        </p>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small">One-Time Password</label>
                            <input
                                type="text"
                                class="form-control form-control-lg text-center letter-spacing"
                                v-model="form.otp"
                                maxlength="6"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                placeholder="000000"
                                required
                            >
                            <div v-if="form.errors.otp" class="text-danger small mt-1">{{ form.errors.otp }}</div>
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary btn-lg w-100 py-3 fw-bold rounded-3"
                            :disabled="form.processing"
                            style="background-color: #27AE60; border: none;"
                        >
                            Verify Email
                        </button>

                        <div class="text-center mt-4">
                            <button
                                type="button"
                                class="btn btn-link text-decoration-none small"
                                @click="sendOtp"
                                :disabled="sendingOtp"
                            >
                                Didn't receive the code? Resend OTP
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    verify_url: String,
    send_otp_url: String,
    has_active_otp: Boolean,
});

const form = useForm({
    otp: '',
});

const sendingOtp = ref(false);
const otpRequested = ref(props.has_active_otp);

const canEnterOtp = computed(() => otpRequested.value || props.has_active_otp);

const submit = () => {
    form.post(props.verify_url);
};

const sendOtp = () => {
    sendingOtp.value = true;
    router.post(props.send_otp_url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            otpRequested.value = true;
        },
        onFinish: () => {
            sendingOtp.value = false;
        },
    });
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
.letter-spacing {
    letter-spacing: 0.35em;
    font-weight: 700;
}
</style>
