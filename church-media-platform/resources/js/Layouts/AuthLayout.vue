<template>
    <div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5 col-xl-4">
                    <div class="card shadow-lg border-0">
                        <div class="card-header bg-white border-0 text-center py-4">
                            <!-- Tenant Logo -->
                            <div v-if="tenant?.branding?.logo_url" class="mb-3">
                                <img 
                                    :src="tenant.branding.logo_url" 
                                    :alt="`${tenant.name} Logo`"
                                    class="img-fluid"
                                    style="max-height: 80px;"
                                >
                            </div>
                            
                            <!-- Fallback to text logo -->
                            <h2 v-else class="mb-0 fw-bold" :style="{ color: tenant?.branding?.primary_color || '#1f2937' }">
                                {{ tenant?.name || 'ForWorship' }}
                            </h2>
                        </div>
                        
                        <div class="card-body px-4 pb-4">
                            <!-- Flash Messages -->
                            <div v-if="$page.props.flash?.success" class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                {{ $page.props.flash.success }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            
                            <div v-if="$page.props.flash?.error" class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                {{ $page.props.flash.error }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            
                            <div v-if="$page.props.flash?.status" class="alert alert-info alert-dismissible fade show mb-3" role="alert">
                                <i class="bi bi-info-circle me-2"></i>
                                {{ $page.props.flash.status }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>

                            <!-- Main Content Slot -->
                            <slot></slot>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            Powered by <a href="#" class="text-decoration-none">ForWorship</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'AuthLayout',
    computed: {
        tenant() {
            return this.$page.props.tenant;
        }
    },
    mounted() {
        // Apply tenant branding colors to CSS variables
        if (this.tenant?.branding) {
            const root = document.documentElement;
            if (this.tenant.branding.primary_color) {
                root.style.setProperty('--bs-primary', this.tenant.branding.primary_color);
            }
            if (this.tenant.branding.secondary_color) {
                root.style.setProperty('--bs-secondary', this.tenant.branding.secondary_color);
            }
        }
    }
}
</script>

<style scoped>
.card {
    border-radius: 1rem;
}

.card-header {
    border-radius: 1rem 1rem 0 0 !important;
}

.btn-primary {
    background-color: var(--bs-primary, #0d6efd);
    border-color: var(--bs-primary, #0d6efd);
}

.btn-primary:hover {
    background-color: var(--bs-primary, #0b5ed7);
    border-color: var(--bs-primary, #0a58ca);
}

.form-control:focus {
    border-color: var(--bs-primary, #86b7fe);
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>