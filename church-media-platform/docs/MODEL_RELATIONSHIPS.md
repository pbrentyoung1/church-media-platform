# Church Media Platform - Model Relationships Documentation

## Overview
This document details the complete model relationship architecture for the Church Media Platform, ensuring proper multi-tenant data isolation and efficient querying.

## Core Architecture Principles

### Multi-Tenancy Design
- **UUID Primary Keys**: All models use UUID primary keys for security and scalability
- **Global Tenant Scoping**: All tenant-owned models automatically filter by `tenant_id`
- **Data Isolation**: Critical security requirement preventing cross-tenant data access

### Relationship Patterns
- **Tenant Ownership**: All content models belong to a tenant
- **User Association**: Users belong to tenants and can own content
- **Content Hierarchy**: Videos organized into Playlists with flexible ordering
- **Analytics Tracking**: Events capture user interactions across platforms

## Model Relationship Map

```
Tenant (1) ──┬── (n) User
             ├── (n) Video
             ├── (n) Playlist  
             ├── (n) Event
             ├── (n) DeviceLink
             └── (n) VideoMetricsDaily

Video (n) ────── (n) Playlist [playlist_items]
Video (1) ────── (n) Event
User (1) ─────── (n) DeviceLink
```

## Detailed Model Relationships

### Tenant Model (`app/Models/Tenant.php`)

The central model for multi-tenancy, representing each church organization.

#### Relationships:
```php
// Users belonging to this tenant
public function users(): HasMany
{
    return $this->hasMany(User::class);
}

// Video content owned by tenant
public function videos(): HasMany
{
    return $this->hasMany(Video::class);
}

// Playlists created by tenant
public function playlists(): HasMany
{
    return $this->hasMany(Playlist::class);
}

// Analytics events for tenant
public function events(): HasMany
{
    return $this->hasMany(Event::class);
}

// Device pairings for TV apps
public function deviceLinks(): HasMany
{
    return $this->hasMany(DeviceLink::class);
}

// Daily aggregated video metrics
public function videoMetrics(): HasMany
{
    return $this->hasMany(VideoMetricsDaily::class);
}
```

#### Key Features:
- UUID primary key for security
- Soft deletes for data retention
- Unique subdomain constraints
- JSON metadata for flexible configuration

### User Model (`app/Models/User.php`)

Represents users within each tenant organization.

#### Relationships:
```php
// Tenant this user belongs to
public function tenant(): BelongsTo
{
    return $this->belongsTo(Tenant::class);
}

// Device links created by this user
public function deviceLinks(): HasMany
{
    return $this->hasMany(DeviceLink::class);
}

// Permission relationships (via Spatie Laravel Permission)
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id', 'role_id');
}
```

#### Multi-Tenant Permission Methods:
```php
public function hasTenantPermission(string $permission): bool
{
    // Avoid circular reference - use static tenant context
    $currentTenant = app()->bound('tenant') ? app('tenant') : null;
    return $this->hasPermissionTo($permission) && 
           $currentTenant && $this->tenant_id === $currentTenant->id;
}

public function getTenantRoles(): array
{
    // Return all user roles (tenant-specific roles would require 
    // custom pivot table with tenant_id column)
    return $this->roles()->pluck('name')->toArray();
}
```

### Video Model (`app/Models/Video.php`)

Core content model representing video assets from various sources.

#### Relationships:
```php
// Tenant that owns this video
public function tenant(): BelongsTo
{
    return $this->belongsTo(Tenant::class);
}

// Playlists containing this video (many-to-many)
public function playlists(): BelongsToMany
{
    return $this->belongsToMany(Playlist::class, 'playlist_items')
        ->withPivot(['position', 'created_at'])
        ->orderByPivot('position');
}

// Analytics events for this video
public function events(): HasMany
{
    return $this->hasMany(Event::class);
}

// Daily metrics aggregation
public function dailyMetrics(): HasMany
{
    return $this->hasMany(VideoMetricsDaily::class);
}
```

#### Key Features:
- Multi-source support (Vimeo, YouTube, Resi, Upload)
- External ID tracking for sync operations
- JSON metadata for flexible video properties
- Status management (published, draft, archived)

### Playlist Model (`app/Models/Playlist.php`)

Organizes videos into collections for structured content delivery.

#### Relationships:
```php
// Tenant that owns this playlist
public function tenant(): BelongsTo
{
    return $this->belongsTo(Tenant::class);
}

// Videos in this playlist (many-to-many with ordering)
public function videos(): BelongsToMany
{
    return $this->belongsToMany(Video::class, 'playlist_items')
        ->withPivot(['position', 'created_at'])
        ->orderByPivot('position');
}
```

#### Pivot Table Features:
The `playlist_items` table includes:
- UUID primary key with auto-generation
- `position` integer for ordering
- Timestamps for audit trail

### Event Model (`app/Models/Event.php`)

Analytics model tracking user interactions across all platforms.

#### Relationships:
```php
// Tenant this event belongs to
public function tenant(): BelongsTo
{
    return $this->belongsTo(Tenant::class);
}

// Video being tracked (optional - some events may be app-level)
public function video(): BelongsTo
{
    return $this->belongsTo(Video::class);
}
```

#### Event Types Tracked:
- `play` - Video playback started
- `pause` - Video paused by user
- `complete` - Video watched to completion
- `seek` - User jumped to different position
- `error` - Playback or technical error

#### Platform Support:
- `roku` - Roku streaming devices
- `appletv` - Apple TV devices  
- `firetv` - Amazon Fire TV devices
- `web` - Web browser interface

### DeviceLink Model (`app/Models/DeviceLink.php`)

Manages device pairing for TV applications (Roku, Apple TV, Fire TV).

#### Relationships:
```php
// Tenant this device link belongs to
public function tenant(): BelongsTo
{
    return $this->belongsTo(Tenant::class);
}

// User who initiated the pairing
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

#### Device Pairing Flow:
```php
public static function generateUserCode(): string
{
    do {
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    } while (static::where('user_code', $code)->exists());
    return $code;
}
```

#### Security Features:
- 6-digit user codes for easy TV input
- Expiration timestamps for security
- Device-specific authentication tokens
- Platform identification for targeted features

## Database Schema Relationships

### Foreign Key Constraints
```sql
-- Core tenant relationships
videos.tenant_id -> tenants.id (UUID)
playlists.tenant_id -> tenants.id (UUID) 
events.tenant_id -> tenants.id (UUID)
users.tenant_id -> tenants.id (UUID)
device_links.tenant_id -> tenants.id (UUID)

-- Content relationships  
playlist_items.playlist_id -> playlists.id (UUID)
playlist_items.video_id -> videos.id (UUID)
events.video_id -> videos.id (UUID, nullable)
device_links.user_id -> users.id (UUID)

-- Metrics relationships
video_metrics_daily.tenant_id -> tenants.id (UUID)
video_metrics_daily.video_id -> videos.id (UUID)
```

### Index Strategy
```sql
-- Performance indexes for common queries
CREATE INDEX idx_videos_tenant_status ON videos(tenant_id, status);
CREATE INDEX idx_playlists_tenant_status ON playlists(tenant_id, status);  
CREATE INDEX idx_events_tenant_occurred ON events(tenant_id, occurred_at);
CREATE INDEX idx_device_links_user_code ON device_links(user_code);
CREATE INDEX idx_playlist_items_position ON playlist_items(playlist_id, position);
```

## Relationship Testing Results

### Comprehensive Test Coverage
All relationships tested and verified working:

```php
// ✅ Tenant relationships
$tenant->users()->count()           // Returns user count
$tenant->videos()->count()          // Returns video count  
$tenant->playlists()->count()       // Returns playlist count
$tenant->events()->count()          // Returns event count
$tenant->deviceLinks()->count()     // Returns device link count

// ✅ Video relationships
$video->tenant                      // Returns owning tenant
$video->playlists()->count()        // Returns playlist associations
$video->events()->count()           // Returns analytics events

// ✅ Playlist relationships  
$playlist->tenant                   // Returns owning tenant
$playlist->videos()->count()        // Returns video count with pivot data

// ✅ Cross-model queries
$playlistVideos = $playlist->videos()
    ->withPivot(['position'])
    ->orderByPivot('position')
    ->get()                         // Returns ordered video collection
```

### Factory Integration Testing
All model factories produce valid data for relationship testing:

```php
// Create complete tenant ecosystem
$tenant = Tenant::factory()->create();
$users = User::factory(5)->forTenant($tenant)->create();
$videos = Video::factory(10)->forTenant($tenant)->create(); 
$playlists = Playlist::factory(3)->forTenant($tenant)->create();

// Test many-to-many relationships
$playlist = $playlists->first();
$playlist->videos()->attach($videos->take(5)->pluck('id'));
$attachedVideos = $playlist->videos; // Should return 5 videos
```

## Performance Considerations

### Eager Loading Patterns
```php
// Efficient tenant data loading
$tenant = Tenant::with([
    'videos' => function ($query) {
        $query->where('status', 'published');
    },
    'playlists.videos',
    'users.roles'
])->find($tenantId);

// Avoid N+1 queries in video listings  
$videos = Video::with(['tenant', 'playlists'])
    ->where('status', 'published')
    ->get();
```

### Query Optimization
- All tenant-scoped queries automatically indexed
- Pivot table relationships use compound indexes
- Event queries partitioned by date for large datasets
- Metrics queries use daily aggregation to reduce load

## Security Considerations

### Tenant Isolation
- Global scopes prevent cross-tenant data access
- All API endpoints validate tenant ownership
- Form requests include tenant-scoped validation rules

### Permission System Integration
```php
// Tenant-aware permission checking
public function canManageVideos(User $user): bool
{
    return $user->hasTenantPermission('videos.manage') &&
           $user->tenant_id === $this->tenant_id;
}
```

This relationship architecture ensures scalable multi-tenancy while maintaining data integrity and security across the Church Media Platform.