<template>
    <div class="min-vh-100 bg-light">
        <!-- Navigation -->
        <Navigation />

        <!-- Main Content -->
        <main class="py-4">
            <div class="container-fluid">
                <!-- Page Header -->
                <div v-if="showHeader" class="row mb-4">
                    <div class="col">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 mb-0">{{ title }}</h1>
                                <p v-if="subtitle" class="text-muted mb-0">{{ subtitle }}</p>
                            </div>
                            <div v-if="$slots.headerActions">
                                <slot name="headerActions"></slot>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Flash Messages -->
                <div class="row mb-4" v-if="hasFlashMessages">
                    <div class="col">
                        <div v-if="$page.props.flash?.success" class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ $page.props.flash.success }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        
                        <div v-if="$page.props.flash?.error" class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            {{ $page.props.flash.error }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        
                        <div v-if="$page.props.flash?.warning" class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            {{ $page.props.flash.warning }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        
                        <div v-if="$page.props.flash?.info || $page.props.flash?.status" class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="bi bi-info-circle me-2"></i>
                            {{ $page.props.flash.info || $page.props.flash.status }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    </div>
                </div>

                <!-- Two-Factor Warning -->
                <div v-if="showTwoFactorWarning" class="row mb-4">
                    <div class="col">
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-shield-exclamation fs-4 me-3"></i>
                                <div class="flex-grow-1">
                                    <strong>Two-Factor Authentication Required</strong>
                                    <div class="small">Your account requires two-factor authentication for enhanced security.</div>
                                </div>
                                <Link 
                                    :href="route('two-factor.setup')"
                                    class="btn btn-warning btn-sm ms-2"
                                >
                                    Set Up Now
                                </Link>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    </div>
                </div>

                <!-- Main Content Slot -->
                <slot></slot>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-top mt-auto py-4">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small class="text-muted">
                            © {{ currentYear }} {{ tenant()?.name || 'ForWorship' }}. All rights reserved.
                        </small>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted">
                            Powered by <a href="#" class="text-decoration-none">ForWorship</a>
                        </small>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'
import Navigation from '@/Components/Navigation.vue'

export default {
    name: 'AppLayout',
    components: {
        Navigation,
        Link,
    },
    props: {
        title: {
            type: String,
            default: 'Dashboard'
        },
        subtitle: {
            type: String,
            default: null
        },
        showHeader: {
            type: Boolean,
            default: true
        }
    },
    computed: {
        currentYear() {
            return new Date().getFullYear()
        },
        hasFlashMessages() {
            const flash = this.$page.props.flash
            return flash?.success || flash?.error || flash?.warning || flash?.info || flash?.status
        },
        showTwoFactorWarning() {
            const user = this.auth()?.user
            const tenant = this.tenant()
            
            if (!user || user.two_factor_enabled) return false
            
            // Show warning if tenant requires 2FA or user has admin role
            const tenantRequires2FA = tenant?.features?.require_2fa
            const userRoles = user.roles || []
            const hasAdminRole = userRoles.some(role => ['admin', 'super-admin'].includes(role))
            
            return tenantRequires2FA || hasAdminRole
        }
    },
    mounted() {
        // Apply tenant branding
        this.applyTenantBranding()
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-dismissible')
            alerts.forEach(alert => {
                const closeBtn = alert.querySelector('.btn-close')
                if (closeBtn && !alert.classList.contains('alert-warning')) {
                    closeBtn.click()
                }
            })
        }, 5000)
    },
    methods: {
        applyTenantBranding() {
            const tenant = this.tenant()
            if (tenant?.branding) {
                const root = document.documentElement
                
                if (tenant.branding.primary_color) {
                    root.style.setProperty('--bs-primary', tenant.branding.primary_color)
                }
                if (tenant.branding.secondary_color) {
                    root.style.setProperty('--bs-secondary', tenant.branding.secondary_color)
                }
            }
        }
    }
}
</script>

<style scoped>
.min-vh-100 {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

main {
    flex: 1;
}

.alert {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.alert-success {
    background-color: #d1e7dd;
    border-left: 4px solid #0f5132;
}

.alert-danger {
    background-color: #f8d7da;
    border-left: 4px solid #842029;
}

.alert-warning {
    background-color: #fff3cd;
    border-left: 4px solid #664d03;
}

.alert-info {
    background-color: #d1ecf1;
    border-left: 4px solid #055160;
}

footer {
    margin-top: auto;
}
</style>