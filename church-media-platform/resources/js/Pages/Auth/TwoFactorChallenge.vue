<template>
    <AuthLayout>
        <div>
            <div class="text-center mb-4">
                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-shield-check text-primary fs-3"></i>
                </div>
                <h4 class="mb-2">Two-Factor Authentication</h4>
                <p class="text-muted small mb-0">
                    Please confirm your identity by entering your authentication code or recovery code.
                </p>
            </div>

            <!-- Toggle between methods -->
            <div class="text-center mb-4">
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="method" id="method-code" v-model="method" value="code" autocomplete="off" checked>
                    <label class="btn btn-outline-primary btn-sm" for="method-code">
                        <i class="bi bi-phone me-1"></i> Authenticator
                    </label>

                    <input type="radio" class="btn-check" name="method" id="method-recovery" v-model="method" value="recovery" autocomplete="off">
                    <label class="btn btn-outline-primary btn-sm" for="method-recovery">
                        <i class="bi bi-key me-1"></i> Recovery Code
                    </label>
                </div>
            </div>

            <form @submit.prevent="submit">
                <!-- TOTP Code Input -->
                <div v-if="method === 'code'" class="mb-4">
                    <label for="code" class="form-label">Authentication Code</label>
                    <input
                        id="code"
                        v-model="form.code"
                        type="text"
                        class="form-control form-control-lg text-center"
                        :class="{ 'is-invalid': form.errors.code }"
                        placeholder="000000"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        autofocus
                        autocomplete="one-time-code"
                        @input="formatCode"
                    >
                    <div v-if="form.errors.code" class="invalid-feedback">
                        {{ form.errors.code }}
                    </div>
                    <small class="form-text text-muted">
                        Enter the 6-digit code from your authenticator app.
                    </small>
                </div>

                <!-- Recovery Code Input -->
                <div v-else-if="method === 'recovery'" class="mb-4">
                    <label for="recovery_code" class="form-label">Recovery Code</label>
                    <input
                        id="recovery_code"
                        v-model="form.recovery_code"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.recovery_code || form.errors.code }"
                        placeholder="Enter your recovery code"
                        autofocus
                        autocomplete="one-time-code"
                    >
                    <div v-if="form.errors.recovery_code || form.errors.code" class="invalid-feedback">
                        {{ form.errors.recovery_code || form.errors.code }}
                    </div>
                    <small class="form-text text-muted">
                        Use one of your backup recovery codes.
                    </small>
                </div>

                <!-- Submit Button -->
                <div class="d-grid mb-3">
                    <button
                        type="submit"
                        class="btn btn-primary btn-lg"
                        :disabled="form.processing || (!form.code && !form.recovery_code)"
                    >
                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                        {{ form.processing ? 'Verifying...' : 'Verify' }}
                    </button>
                </div>

                <!-- Back to Login -->
                <div class="text-center">
                    <Link
                        :href="route('login')"
                        class="text-decoration-none small"
                    >
                        <i class="bi bi-arrow-left me-1"></i>
                        Back to login
                    </Link>
                </div>
            </form>
        </div>
    </AuthLayout>
</template>

<script>
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

export default {
    name: 'TwoFactorChallenge',
    components: {
        AuthLayout,
        Link,
    },
    props: {
        tenant: Object,
    },
    setup() {
        const method = ref('code')
        
        const form = useForm({
            code: '',
            recovery_code: '',
        })

        const submit = () => {
            form.post(route('two-factor.verify'))
        }

        const formatCode = (event) => {
            // Only allow digits
            const value = event.target.value.replace(/\D/g, '')
            form.code = value.slice(0, 6)
            
            // Auto-submit when 6 digits are entered
            if (value.length === 6) {
                setTimeout(() => {
                    if (!form.processing) {
                        submit()
                    }
                }, 300)
            }
        }

        return {
            method,
            form,
            submit,
            formatCode,
        }
    },
    watch: {
        method(newValue) {
            // Clear form when switching methods
            this.form.reset()
            this.form.clearErrors()
        }
    },
    mounted() {
        this.$nextTick(() => {
            if (this.method === 'code') {
                this.$el.querySelector('#code')?.focus()
            } else {
                this.$el.querySelector('#recovery_code')?.focus()
            }
        })
    }
}
</script>

<style scoped>
.btn-group .btn-check:checked + .btn {
    background-color: var(--bs-primary, #0d6efd);
    border-color: var(--bs-primary, #0d6efd);
    color: white;
}

.form-control-lg {
    font-size: 1.5rem;
    letter-spacing: 0.5rem;
    font-weight: 600;
}

#code {
    font-family: 'Courier New', monospace;
}

.bg-primary.bg-opacity-10 {
    background-color: rgba(13, 110, 253, 0.1) !important;
}
</style>