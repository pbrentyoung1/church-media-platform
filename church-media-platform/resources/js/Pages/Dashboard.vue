<template>
    <AppLayout title="Dashboard" subtitle="Welcome to your church media platform">
        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="bi bi-play-circle text-primary fs-4"></i>
                            </div>
                            <div>
                                <div class="h4 mb-0">{{ metrics.total_videos || 0 }}</div>
                                <div class="text-muted small">Total Videos</div>
                                <div v-if="metrics.videos_this_month > 0" class="text-success small">
                                    <i class="bi bi-arrow-up"></i>
                                    {{ metrics.videos_this_month }} this month
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="bi bi-collection-play text-success fs-4"></i>
                            </div>
                            <div>
                                <div class="h4 mb-0">{{ metrics.total_playlists || 0 }}</div>
                                <div class="text-muted small">Playlists</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="bi bi-calendar-event text-warning fs-4"></i>
                            </div>
                            <div>
                                <div class="h4 mb-0">{{ metrics.total_events || 0 }}</div>
                                <div class="text-muted small">Events</div>
                                <div v-if="metrics.events_this_month > 0" class="text-success small">
                                    <i class="bi bi-arrow-up"></i>
                                    {{ metrics.events_this_month }} this month
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="bg-info bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="bi bi-people text-info fs-4"></i>
                            </div>
                            <div>
                                <div class="h4 mb-0">{{ metrics.active_users || 0 }}</div>
                                <div class="text-muted small">Active Users</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Activities -->
            <div class="col-lg-8 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pb-0">
                        <h5 class="card-title mb-0">Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <div v-if="recentActivities.length === 0" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-1 mb-3 d-block"></i>
                            <p>No recent activities</p>
                        </div>
                        
                        <div v-else>
                            <div 
                                v-for="activity in recentActivities" 
                                :key="`${activity.type}-${activity.title}-${activity.created_at}`"
                                class="d-flex align-items-start py-3 border-bottom"
                            >
                                <div class="me-3 mt-1">
                                    <i :class="getActivityIcon(activity.type)" class="fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">{{ activity.title }}</div>
                                    <div class="text-muted small">
                                        by {{ activity.user }} • {{ formatRelative(activity.created_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pb-0">
                        <h5 class="card-title mb-0">Upcoming Events</h5>
                    </div>
                    <div class="card-body">
                        <div v-if="upcomingEvents.length === 0" class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x fs-1 mb-3 d-block"></i>
                            <p class="mb-0">No upcoming events</p>
                        </div>
                        
                        <div v-else>
                            <div 
                                v-for="event in upcomingEvents" 
                                :key="event.id"
                                class="mb-3 pb-3 border-bottom"
                            >
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <div class="bg-primary bg-opacity-10 rounded p-2">
                                            <i class="bi bi-calendar-event text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ event.title }}</div>
                                        <div class="text-muted small mb-1">
                                            {{ formatDate(event.start_time, { 
                                                month: 'short', 
                                                day: 'numeric',
                                                hour: 'numeric',
                                                minute: '2-digit'
                                            }) }}
                                        </div>
                                        <div v-if="event.is_live" class="badge bg-danger small">
                                            <i class="bi bi-broadcast me-1"></i>
                                            Live
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div v-if="can('can_manage_events')" class="text-center mt-3">
                            <Link 
                                :href="route('content.events')" 
                                class="btn btn-outline-primary btn-sm"
                            >
                                Manage Events
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Popular Content (if analytics available) -->
        <div v-if="popularContent?.videos?.length > 0" class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pb-0">
                        <h5 class="card-title mb-0">Popular Videos This Week</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div 
                                v-for="video in popularContent.videos.slice(0, 4)" 
                                :key="video.id"
                                class="col-md-6 col-lg-3 mb-3"
                            >
                                <div class="card border-0 bg-light">
                                    <div class="position-relative">
                                        <img 
                                            :src="video.thumbnail_url || '/placeholder-video.jpg'"
                                            :alt="video.title"
                                            class="card-img-top"
                                            style="height: 120px; object-fit: cover;"
                                        >
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <span class="badge bg-dark bg-opacity-75">
                                                {{ video.views }} views
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body p-3">
                                        <h6 class="card-title mb-0 text-truncate">{{ video.title }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
    name: 'Dashboard',
    components: {
        AppLayout,
        Link,
    },
    props: {
        user: Object,
        tenant: Object,
        metrics: {
            type: Object,
            default: () => ({})
        },
        recentActivities: {
            type: Array,
            default: () => []
        },
        upcomingEvents: {
            type: Array,
            default: () => []
        },
        popularContent: {
            type: Object,
            default: () => ({})
        },
        permissions: {
            type: Object,
            default: () => ({})
        }
    },
    methods: {
        getActivityIcon(type) {
            const icons = {
                'video': 'bi bi-play-circle text-primary',
                'playlist': 'bi bi-collection-play text-success',
                'event': 'bi bi-calendar-event text-warning',
                'user': 'bi bi-person text-info',
                default: 'bi bi-circle text-secondary'
            }
            return icons[type] || icons.default
        }
    },
    mounted() {
        // Welcome message for first-time users
        if (this.user && !this.user.last_login_at) {
            this.$nextTick(() => {
                // Could show a welcome modal or tour here
                console.log('Welcome to ForWorship!')
            })
        }
    }
}
</script>

<style scoped>
.card {
    border-radius: 1rem;
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.border-bottom:last-child {
    border-bottom: none !important;
}

.bg-opacity-10 {
    --bs-bg-opacity: 0.1;
}

.card-img-top {
    border-radius: 1rem 1rem 0 0;
}

.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>