<template>
    <AdminLayout title="Change Password">
        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Change Password</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <div v-if="$page.props.flash?.success" class="alert alert-success small">
                                {{ $page.props.flash.success }}
                            </div>

                            <p class="text-muted small mb-4">
                                Update the password for <strong>{{ user.email }}</strong>.
                            </p>

                            <PasswordInput
                                v-model="form.current_password"
                                label="Current Password *"
                                wrapper-class="mb-3"
                                autocomplete="current-password"
                                :error="form.errors.current_password"
                                required
                            />

                            <PasswordInput
                                v-model="form.password"
                                label="New Password *"
                                wrapper-class="mb-3"
                                autocomplete="new-password"
                                :error="form.errors.password"
                                :minlength="8"
                                required
                            />

                            <PasswordInput
                                v-model="form.password_confirmation"
                                label="Confirm New Password *"
                                wrapper-class="mb-3"
                                autocomplete="new-password"
                                :error="form.errors.password_confirmation"
                                :minlength="8"
                                required
                            />

                            <p class="text-muted small mb-0">
                                Password must be at least 8 characters and include uppercase and lowercase letters, a number, and a symbol (e.g. ! @ # $). Other active sessions will be signed out after a successful change.
                            </p>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <Link href="/" class="btn btn-default">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save me-1"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PasswordInput from '@/Components/PasswordInput.vue';

defineProps({
    user: Object,
});

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put('/password', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>
