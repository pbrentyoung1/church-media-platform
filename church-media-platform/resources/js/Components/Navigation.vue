<template>
    <nav class="navbar navbar-expand-lg" :style="{ backgroundColor: tenant?.branding?.primary_color || '#1f2937' }">
        <div class="container-fluid">
            <!-- Brand -->
            <Link class="navbar-brand d-flex align-items-center text-white" :href="route('dashboard')">
                <img 
                    v-if="tenant?.branding?.logo_url" 
                    :src="tenant.branding.logo_url" 
                    :alt="`${tenant?.name} Logo`"
                    class="me-2"
                    style="height: 32px;"
                >
                <span class="fw-bold">{{ tenant?.name || 'ForWorship' }}</span>
            </Link>

            <!-- Toggle button for mobile -->
            <button 
                class="navbar-toggler" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#navbarNav"
                aria-controls="navbarNav" 
                aria-expanded="false" 
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Main Navigation -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <Link 
                            class="nav-link text-white" 
                            :class="{ active: $page.component === 'Dashboard' }"
                            :href="route('dashboard')"
                        >
                            <i class="bi bi-house-door me-1"></i>
                            Dashboard
                        </Link>
                    </li>
                    
                    <li v-if="can('can_manage_videos')" class="nav-item">
                        <Link 
                            class="nav-link text-white" 
                            :class="{ active: $page.component.startsWith('Content/Video') }"
                            :href="route('content.videos')"
                        >
                            <i class="bi bi-play-circle me-1"></i>
                            Videos
                        </Link>
                    </li>
                    
                    <li v-if="can('can_manage_playlists')" class="nav-item">
                        <Link 
                            class="nav-link text-white" 
                            :class="{ active: $page.component.startsWith('Content/Playlist') }"
                            :href="route('content.playlists')"
                        >
                            <i class="bi bi-collection-play me-1"></i>
                            Playlists
                        </Link>
                    </li>
                    
                    <li v-if="can('can_manage_events')" class="nav-item">
                        <Link 
                            class="nav-link text-white" 
                            :class="{ active: $page.component.startsWith('Content/Event') }"
                            :href="route('content.events')"
                        >
                            <i class="bi bi-calendar-event me-1"></i>
                            Events
                        </Link>
                    </li>
                    
                    <li v-if="can('can_view_analytics')" class="nav-item">
                        <Link 
                            class="nav-link text-white" 
                            :class="{ active: $page.component.startsWith('Analytics') }"
                            href="#"
                        >
                            <i class="bi bi-graph-up me-1"></i>
                            Analytics
                        </Link>
                    </li>
                </ul>

                <!-- User Menu -->
                <ul class="navbar-nav">
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a 
                            class="nav-link text-white position-relative" 
                            href="#" 
                            role="button" 
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <i class="bi bi-bell fs-5"></i>
                            <span v-if="notificationCount > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ notificationCount > 9 ? '9+' : notificationCount }}
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <li v-if="notifications.length === 0">
                                <span class="dropdown-item-text text-muted">No new notifications</span>
                            </li>
                            <li v-for="notification in notifications" :key="notification.id">
                                <a class="dropdown-item" href="#">
                                    <div class="d-flex align-items-start">
                                        <i :class="getNotificationIcon(notification.type)" class="me-2 mt-1"></i>
                                        <div>
                                            <div class="fw-semibold">{{ notification.title }}</div>
                                            <small class="text-muted">{{ formatRelative(notification.created_at) }}</small>
                                        </div>
                                    </div>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="#">View all notifications</a></li>
                        </ul>
                    </li>

                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a 
                            class="nav-link dropdown-toggle text-white d-flex align-items-center" 
                            href="#" 
                            role="button" 
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <div class="bg-white bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                <i class="bi bi-person text-white"></i>
                            </div>
                            {{ auth()?.user?.name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <h6 class="dropdown-header">
                                    {{ auth()?.user?.name }}
                                    <div class="small text-muted">{{ auth()?.user?.email }}</div>
                                </h6>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <Link class="dropdown-item" href="#">
                                    <i class="bi bi-person me-2"></i>
                                    Profile Settings
                                </Link>
                            </li>
                            <li>
                                <Link class="dropdown-item" :href="route('two-factor.setup')">
                                    <i class="bi bi-shield-check me-2"></i>
                                    Two-Factor Auth
                                    <span v-if="!auth()?.user?.two_factor_enabled" class="badge bg-warning ms-2">Setup Required</span>
                                </Link>
                            </li>
                            <li v-if="can('can_manage_settings')">
                                <Link class="dropdown-item" :href="route('admin.settings')">
                                    <i class="bi bi-gear me-2"></i>
                                    Admin Settings
                                </Link>
                            </li>
                            <li v-if="can('can_manage_users')">
                                <Link class="dropdown-item" :href="route('admin.users')">
                                    <i class="bi bi-people me-2"></i>
                                    Manage Users
                                </Link>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <Link 
                                    class="dropdown-item text-danger" 
                                    :href="route('logout')" 
                                    method="post" 
                                    as="button"
                                >
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Sign Out
                                </Link>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</template>

<script>
import { Link } from '@inertiajs/vue3'

export default {
    name: 'Navigation',
    components: {
        Link,
    },
    data() {
        return {
            notifications: [
                // Mock notifications - replace with real data
            ],
        }
    },
    computed: {
        tenant() {
            return this.$page.props.shared?.tenant
        },
        notificationCount() {
            return this.notifications.length
        },
    },
    methods: {
        getNotificationIcon(type) {
            const icons = {
                'success': 'bi bi-check-circle text-success',
                'warning': 'bi bi-exclamation-triangle text-warning',
                'error': 'bi bi-x-circle text-danger',
                'info': 'bi bi-info-circle text-info',
                default: 'bi bi-bell text-primary'
            }
            return icons[type] || icons.default
        },
    }
}
</script>

<style scoped>
.navbar-brand:hover {
    opacity: 0.9;
}

.nav-link.active {
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 0.375rem;
}

.nav-link:hover {
    background-color: rgba(255, 255, 255, 0.05);
    border-radius: 0.375rem;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border-radius: 0.5rem;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    margin: 0 0.25rem;
}

.dropdown-item:hover {
    background-color: var(--bs-primary, #0d6efd);
    color: white;
}

.navbar-toggler {
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.85%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}
</style>