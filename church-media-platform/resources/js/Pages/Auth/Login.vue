<template>
    <AuthLayout>
        <div>
            <h4 class="mb-4 text-center">Sign In</h4>
            
            <form @submit.prevent="submit">
                <!-- Email Field -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.email }"
                        required
                        autofocus
                        autocomplete="username"
                    >
                    <div v-if="form.errors.email" class="invalid-feedback">
                        {{ form.errors.email }}
                    </div>
                </div>

                <!-- Password Field -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.password }"
                        required
                        autocomplete="current-password"
                    >
                    <div v-if="form.errors.password" class="invalid-feedback">
                        {{ form.errors.password }}
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="mb-3 form-check">
                    <input
                        id="remember"
                        v-model="form.remember"
                        type="checkbox"
                        class="form-check-input"
                    >
                    <label for="remember" class="form-check-label">
                        Remember me
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="d-grid mb-3">
                    <button
                        type="submit"
                        class="btn btn-primary btn-lg"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                        {{ form.processing ? 'Signing In...' : 'Sign In' }}
                    </button>
                </div>

                <!-- Password Reset Link -->
                <div class="text-center" v-if="canResetPassword">
                    <Link
                        :href="route('password.request')"
                        class="text-decoration-none small"
                    >
                        Forgot your password?
                    </Link>
                </div>
            </form>

            <!-- Tenant Information -->
            <div v-if="tenant" class="mt-4 pt-3 border-top text-center">
                <small class="text-muted">
                    Signing in to <strong>{{ tenant.name }}</strong>
                </small>
            </div>
        </div>
    </AuthLayout>
</template>

<script>
import { useForm, Link } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

export default {
    name: 'Login',
    components: {
        AuthLayout,
        Link,
    },
    props: {
        canResetPassword: Boolean,
        status: String,
        tenant: Object,
    },
    setup(props) {
        const form = useForm({
            email: '',
            password: '',
            remember: false,
        })

        const submit = () => {
            form.post(route('login'), {
                onFinish: () => form.reset('password'),
            })
        }

        return {
            form,
            submit,
        }
    },
    mounted() {
        // Auto-focus email field if empty
        if (!this.form.email) {
            this.$nextTick(() => {
                this.$el.querySelector('#email').focus()
            })
        }
    }
}
</script>

<style scoped>
.form-control {
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
}

.btn {
    border-radius: 0.5rem;
    font-weight: 500;
}

.form-check-input:checked {
    background-color: var(--bs-primary, #0d6efd);
    border-color: var(--bs-primary, #0d6efd);
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}
</style>