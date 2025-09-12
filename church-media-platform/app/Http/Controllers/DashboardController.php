<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Playlist;
use App\Models\User;
use App\Models\Video;
use App\Services\TenantService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    // Middleware is applied in routes, not in constructor for Laravel 12

    /**
     * Display the tenant dashboard
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $tenant = TenantService::current();
        
        // Get dashboard metrics
        $metrics = $this->getDashboardMetrics();
        
        // Get recent activities
        $recentActivities = $this->getRecentActivities();
        
        // Get upcoming events
        $upcomingEvents = $this->getUpcomingEvents();
        
        // Get popular content
        $popularContent = $this->getPopularContent();
        
        // Get tenant-specific data
        $tenantData = $this->getTenantData($tenant);

        return Inertia::render('Dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getTenantRoles(),
                'two_factor_enabled' => $user->two_factor_enabled,
                'email_verified_at' => $user->email_verified_at,
            ],
            'tenant' => $tenantData,
            'metrics' => $metrics,
            'recentActivities' => $recentActivities,
            'upcomingEvents' => $upcomingEvents,
            'popularContent' => $popularContent,
            'permissions' => $this->getUserPermissions($user),
        ]);
    }

    /**
     * Get dashboard metrics for the tenant
     */
    private function getDashboardMetrics(): array
    {
        $tenant = TenantService::current();
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        return [
            'total_videos' => Video::where('tenant_id', $tenant->id)->count(),
            'total_playlists' => Playlist::where('tenant_id', $tenant->id)->count(),
            'total_events' => Event::where('tenant_id', $tenant->id)->count(),
            'total_users' => User::where('tenant_id', $tenant->id)->count(),
            'videos_this_month' => Video::where('tenant_id', $tenant->id)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->count(),
            'events_this_month' => Event::where('tenant_id', $tenant->id)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->count(),
            'active_users' => User::where('tenant_id', $tenant->id)
                ->where('last_login_at', '>=', $thirtyDaysAgo)
                ->count(),
        ];
    }

    /**
     * Get recent activities for the tenant
     */
    private function getRecentActivities(): array
    {
        $tenant = TenantService::current();
        
        // Get recent videos
        $recentVideos = Video::where('tenant_id', $tenant->id)
            ->with(['user:id,name'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($video) {
                return [
                    'type' => 'video',
                    'title' => $video->title,
                    'user' => $video->user->name,
                    'created_at' => $video->created_at,
                ];
            });

        // Get recent playlists
        $recentPlaylists = Playlist::where('tenant_id', $tenant->id)
            ->with(['user:id,name'])
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($playlist) {
                return [
                    'type' => 'playlist',
                    'title' => $playlist->name,
                    'user' => $playlist->user->name,
                    'created_at' => $playlist->created_at,
                ];
            });

        // Get recent events
        $recentEvents = Event::where('tenant_id', $tenant->id)
            ->with(['user:id,name'])
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($event) {
                return [
                    'type' => 'event',
                    'title' => $event->title,
                    'user' => $event->user->name,
                    'created_at' => $event->created_at,
                ];
            });

        return $recentVideos
            ->concat($recentPlaylists)
            ->concat($recentEvents)
            ->sortByDesc('created_at')
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * Get upcoming events for the tenant
     */
    private function getUpcomingEvents(): array
    {
        $tenant = TenantService::current();
        
        return Event::where('tenant_id', $tenant->id)
            ->where('start_time', '>', Carbon::now())
            ->orderBy('start_time')
            ->take(5)
            ->get([
                'id',
                'title',
                'description',
                'start_time',
                'end_time',
                'is_live',
                'stream_url'
            ])
            ->toArray();
    }

    /**
     * Get popular content analytics
     */
    private function getPopularContent(): array
    {
        $tenant = TenantService::current();
        $sevenDaysAgo = Carbon::now()->subDays(7);
        
        // Get most viewed videos (if metrics table exists)
        $popularVideos = Video::where('tenant_id', $tenant->id)
            ->with(['metrics' => function ($query) use ($sevenDaysAgo) {
                $query->where('date', '>=', $sevenDaysAgo)
                    ->select('video_id', DB::raw('SUM(views) as total_views'))
                    ->groupBy('video_id');
            }])
            ->get()
            ->sortByDesc(function ($video) {
                return $video->metrics->sum('total_views');
            })
            ->take(5)
            ->map(function ($video) {
                return [
                    'id' => $video->id,
                    'title' => $video->title,
                    'thumbnail_url' => $video->thumbnail_url,
                    'views' => $video->metrics->sum('total_views'),
                ];
            })
            ->values()
            ->all();

        return [
            'videos' => $popularVideos,
        ];
    }

    /**
     * Get tenant-specific data
     */
    private function getTenantData($tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'subdomain' => $tenant->subdomain,
            'status' => $tenant->status,
            'is_active' => $tenant->isActive(),
            'is_on_trial' => $tenant->isOnTrial(),
            'trial_ends_at' => $tenant->trial_ends_at,
            'subscription_ends_at' => $tenant->subscription_ends_at,
            'branding' => $tenant->getBrandingConfig(),
            'features' => $tenant->feature_settings ?? [],
        ];
    }

    /**
     * Get user permissions within the tenant
     */
    private function getUserPermissions($user): array
    {
        $permissions = [];
        
        // Check basic permissions
        $permissions['can_manage_videos'] = $user->can('manage videos') || $user->hasRole(['admin', 'editor']);
        $permissions['can_manage_playlists'] = $user->can('manage playlists') || $user->hasRole(['admin', 'editor']);
        $permissions['can_manage_events'] = $user->can('manage events') || $user->hasRole(['admin', 'editor']);
        $permissions['can_manage_users'] = $user->can('manage users') || $user->hasRole(['admin']);
        $permissions['can_manage_settings'] = $user->can('manage settings') || $user->hasRole(['admin']);
        $permissions['can_view_analytics'] = $user->can('view analytics') || $user->hasRole(['admin', 'editor']);
        
        // Check advanced permissions
        $permissions['can_delete_videos'] = $user->can('delete videos') || $user->hasRole(['admin']);
        $permissions['can_publish_events'] = $user->can('publish events') || $user->hasRole(['admin', 'editor']);
        $permissions['can_manage_branding'] = $user->can('manage branding') || $user->hasRole(['admin']);
        
        return $permissions;
    }
}