<template>
    <AuthLayout>
        <div>
            <div class="text-center mb-4">
                <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-shield-plus text-warning fs-3"></i>
                </div>
                <h4 class="mb-2">Two-Factor Authentication Required</h4>
                <p class="text-muted small mb-0">
                    {{ tenant?.requires_2fa ? 'Your organization requires' : 'Your role requires' }} 
                    two-factor authentication for enhanced security.
                </p>
            </div>

            <!-- Setup Steps -->
            <div v-if="!user.two_factor_enabled">
                <!-- Step 1: Enable 2FA -->
                <div v-if="currentStep === 1" class="text-center">
                    <h5 class="mb-3">Step 1: Enable Two-Factor Authentication</h5>
                    <p class="text-muted mb-4">
                        Click the button below to enable two-factor authentication and generate your QR code.
                    </p>
                    
                    <button
                        @click="enableTwoFactor"
                        class="btn btn-primary btn-lg"
                        :disabled="enabling"
                    >
                        <span v-if="enabling" class="spinner-border spinner-border-sm me-2"></span>
                        {{ enabling ? 'Enabling...' : 'Enable Two-Factor Authentication' }}
                    </button>
                </div>

                <!-- Step 2: Scan QR Code -->
                <div v-else-if="currentStep === 2">
                    <h5 class="mb-3 text-center">Step 2: Scan QR Code</h5>
                    <p class="text-muted text-center mb-4">
                        Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.).
                    </p>

                    <!-- QR Code Display -->
                    <div class="text-center mb-4">
                        <div v-if="qrCode" class="d-inline-block p-3 bg-white rounded border">
                            <div v-html="qrCode"></div>
                        </div>
                        <div v-else class="d-inline-block p-4 bg-light rounded">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading QR code...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Manual entry option -->
                    <div class="text-center mb-4">
                        <button
                            @click="showManualEntry = !showManualEntry"
                            class="btn btn-link btn-sm"
                        >
                            Can't scan? Enter code manually
                        </button>
                        
                        <div v-if="showManualEntry" class="mt-3 p-3 bg-light rounded">
                            <small class="text-muted">Enter this code in your authenticator app:</small>
                            <div class="font-monospace fw-bold mt-2">{{ secretKey }}</div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button
                            @click="currentStep = 3"
                            class="btn btn-primary"
                        >
                            I've Added the Account
                        </button>
                    </div>
                </div>

                <!-- Step 3: Confirm Setup -->
                <div v-else-if="currentStep === 3">
                    <h5 class="mb-3 text-center">Step 3: Confirm Setup</h5>
                    <p class="text-muted text-center mb-4">
                        Enter the 6-digit code from your authenticator app to confirm the setup.
                    </p>

                    <form @submit.prevent="confirmTwoFactor">
                        <div class="mb-4">
                            <label for="confirmation_code" class="form-label">Confirmation Code</label>
                            <input
                                id="confirmation_code"
                                v-model="confirmationForm.code"
                                type="text"
                                class="form-control form-control-lg text-center"
                                :class="{ 'is-invalid': confirmationForm.errors.code }"
                                placeholder="000000"
                                maxlength="6"
                                pattern="[0-9]{6}"
                                autofocus
                                autocomplete="one-time-code"
                                @input="formatConfirmationCode"
                            >
                            <div v-if="confirmationForm.errors.code" class="invalid-feedback">
                                {{ confirmationForm.errors.code }}
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button
                                type="submit"
                                class="btn btn-success btn-lg"
                                :disabled="confirmationForm.processing || confirmationForm.code.length !== 6"
                            >
                                <span v-if="confirmationForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                                {{ confirmationForm.processing ? 'Confirming...' : 'Confirm & Enable' }}
                            </button>
                        </div>

                        <div class="text-center">
                            <button
                                type="button"
                                @click="currentStep = 2"
                                class="btn btn-link btn-sm"
                            >
                                <i class="bi bi-arrow-left me-1"></i>
                                Back to QR Code
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Already Enabled State -->
            <div v-else class="text-center">
                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-shield-check text-success fs-3"></i>
                </div>
                <h5 class="text-success mb-3">Two-Factor Authentication Enabled</h5>
                <p class="text-muted mb-4">
                    Your account is secured with two-factor authentication.
                </p>
                
                <div class="d-grid">
                    <Link
                        :href="route('dashboard')"
                        class="btn btn-primary btn-lg"
                    >
                        Continue to Dashboard
                    </Link>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

export default {
    name: 'TwoFactorSetup',
    components: {
        AuthLayout,
        Link,
    },
    props: {
        user: Object,
        tenant: Object,
    },
    setup(props) {
        const currentStep = ref(1)
        const enabling = ref(false)
        const qrCode = ref(null)
        const secretKey = ref('')
        const showManualEntry = ref(false)

        const confirmationForm = useForm({
            code: '',
        })

        const enableTwoFactor = async () => {
            enabling.value = true
            try {
                await router.post(route('two-factor.enable'), {}, {
                    onSuccess: () => {
                        currentStep.value = 2
                        fetchQrCode()
                    },
                    onError: (errors) => {
                        console.error('Failed to enable 2FA:', errors)
                    },
                    onFinish: () => {
                        enabling.value = false
                    }
                })
            } catch (error) {
                enabling.value = false
                console.error('Error enabling 2FA:', error)
            }
        }

        const fetchQrCode = async () => {
            try {
                const response = await fetch(route('two-factor.qr-code'), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    }
                })
                
                if (response.ok) {
                    const data = await response.json()
                    qrCode.value = data.svg
                    // Extract secret key from URL for manual entry
                    const urlMatch = data.url.match(/secret=([A-Z2-7]+)/i)
                    if (urlMatch) {
                        secretKey.value = urlMatch[1]
                    }
                }
            } catch (error) {
                console.error('Failed to fetch QR code:', error)
            }
        }

        const confirmTwoFactor = () => {
            confirmationForm.post(route('two-factor.confirm'), {
                onSuccess: () => {
                    // Redirect to dashboard on success
                    router.visit(route('dashboard'))
                }
            })
        }

        const formatConfirmationCode = (event) => {
            const value = event.target.value.replace(/\D/g, '')
            confirmationForm.code = value.slice(0, 6)
        }

        return {
            currentStep,
            enabling,
            qrCode,
            secretKey,
            showManualEntry,
            confirmationForm,
            enableTwoFactor,
            confirmTwoFactor,
            formatConfirmationCode,
        }
    },
    mounted() {
        // If 2FA is already enabled, show success state
        if (this.user.two_factor_enabled) {
            this.currentStep = 4
        }
    }
}
</script>

<style scoped>
.form-control-lg {
    font-size: 1.5rem;
    letter-spacing: 0.5rem;
    font-weight: 600;
    font-family: 'Courier New', monospace;
}

.font-monospace {
    font-family: 'Courier New', monospace !important;
    font-size: 0.9rem;
    word-break: break-all;
}

svg {
    max-width: 200px;
    height: auto;
}
</style>