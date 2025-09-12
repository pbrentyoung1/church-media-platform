# Church Media Platform - Database Schema Documentation

## Overview
This document details the complete database schema for the Church Media Platform, including tables, relationships, indexes, and multi-tenant architecture considerations.

## Database Architecture

### Technology Stack
- **Database**: PostgreSQL (production) / SQLite (testing)
- **Primary Keys**: UUID for all tables (security & scalability)
- **Multi-Tenancy**: Global tenant scoping with `tenant_id` foreign keys
- **Migrations**: Laravel migration system with rollback support

### Core Design Principles
1. **Tenant Isolation**: All tenant-owned data includes `tenant_id` for complete isolation
2. **UUID Primary Keys**: Non-sequential IDs for enhanced security
3. **Soft Deletes**: Data retention for audit and recovery purposes
4. **JSON Metadata**: Flexible schema evolution without migrations
5. **Indexed Performance**: Strategic indexes for common query patterns

---

## Core Tables

### tenants
Central table for multi-tenant architecture, representing each church organization.

```sql
CREATE TABLE tenants (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    subdomain VARCHAR(255) UNIQUE NOT NULL,
    domain VARCHAR(255) UNIQUE NULL,
    settings JSON NULL,
    branding JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
);

-- Indexes
CREATE INDEX idx_tenants_subdomain ON tenants(subdomain);
CREATE INDEX idx_tenants_domain ON tenants(domain);
CREATE INDEX idx_tenants_deleted_at ON tenants(deleted_at);
```

**Key Features**:
- `subdomain`: Unique identifier for tenant routing (e.g., `firstchurch.churchapp.com`)
- `domain`: Optional custom domain mapping (e.g., `media.firstchurch.org`)
- `settings`: JSON configuration (features, limits, preferences)
- `branding`: JSON branding data (logo, colors, theme)

### users  
User accounts within each tenant organization.

```sql
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    two_factor_confirmed_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    CONSTRAINT users_email_tenant_unique UNIQUE(email, tenant_id)
);

-- Indexes
CREATE INDEX idx_users_tenant_id ON users(tenant_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_deleted_at ON users(deleted_at);
```

**Security Features**:
- Email unique per tenant (same email can exist across tenants)
- Two-factor authentication support (TOTP)
- Recovery codes for 2FA backup
- Soft deletes for audit trail

---

## Content Management Tables

### videos
Core content table storing video metadata and external references.

```sql
CREATE TABLE videos (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    duration INTEGER NULL, -- Duration in seconds
    source VARCHAR(50) NOT NULL, -- 'vimeo', 'youtube', 'resi', 'upload'
    external_id VARCHAR(255) NULL, -- External API ID
    playback_url TEXT NULL,
    thumbnail_url TEXT NULL, 
    captions_url TEXT NULL,
    status VARCHAR(50) DEFAULT 'draft', -- 'published', 'draft', 'archived'
    metadata JSON NULL,
    synced_at TIMESTAMP NULL, -- Last sync with external service
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    CONSTRAINT videos_external_tenant_source_unique 
        UNIQUE(external_id, tenant_id, source)
);

-- Performance indexes
CREATE INDEX idx_videos_tenant_status ON videos(tenant_id, status);
CREATE INDEX idx_videos_source ON videos(source);
CREATE INDEX idx_videos_synced_at ON videos(synced_at);
CREATE INDEX idx_videos_deleted_at ON videos(deleted_at);
```

**Metadata JSON Structure**:
```json
{
    "resolution": "1080p",
    "fps": 30,
    "codec": "H.264", 
    "bitrate": 5000,
    "category": "sermon",
    "tags": ["sunday", "series-name"],
    "speaker": "Pastor Name"
}
```

### playlists
Content organization for structured video delivery.

```sql
CREATE TABLE playlists (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NULL,
    description TEXT NULL,
    thumbnail_url TEXT NULL,
    sort_order INTEGER DEFAULT 0,
    status VARCHAR(50) DEFAULT 'draft', -- 'published', 'draft', 'archived'  
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    CONSTRAINT playlists_slug_tenant_unique UNIQUE(slug, tenant_id)
);

-- Indexes
CREATE INDEX idx_playlists_tenant_status ON playlists(tenant_id, status);
CREATE INDEX idx_playlists_sort_order ON playlists(sort_order);
CREATE INDEX idx_playlists_deleted_at ON playlists(deleted_at);
```

### playlist_items
Many-to-many relationship between playlists and videos with ordering.

```sql
CREATE TABLE playlist_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    playlist_id UUID NOT NULL REFERENCES playlists(id) ON DELETE CASCADE,
    video_id UUID NOT NULL REFERENCES videos(id) ON DELETE CASCADE,
    position INTEGER DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    CONSTRAINT playlist_items_unique UNIQUE(playlist_id, video_id)
);

-- Indexes for performance
CREATE INDEX idx_playlist_items_position ON playlist_items(playlist_id, position);
CREATE INDEX idx_playlist_items_video_id ON playlist_items(video_id);
```

**Key Features**:
- UUID primary key with auto-generation (fixed in migration)
- Position-based ordering within playlists
- Unique constraint prevents duplicate video-playlist pairs
- Cascade deletes maintain referential integrity

---

## Analytics & Tracking Tables

### events  
User interaction analytics across all platforms.

```sql
CREATE TABLE events (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    video_id UUID NULL REFERENCES videos(id) ON DELETE SET NULL,
    platform VARCHAR(50) NOT NULL, -- 'roku', 'appletv', 'firetv', 'web'
    event_type VARCHAR(50) NOT NULL, -- 'play', 'pause', 'complete', 'seek', 'error'
    seconds INTEGER NULL, -- Position in video (for seek/play events)
    metadata JSON NULL,
    occurred_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Analytics performance indexes
CREATE INDEX idx_events_tenant_occurred ON events(tenant_id, occurred_at);
CREATE INDEX idx_events_video_type ON events(video_id, event_type);
CREATE INDEX idx_events_platform ON events(platform);
CREATE INDEX idx_events_occurred_at ON events(occurred_at);
```

**Event Metadata Examples**:
```json
{
    "device_model": "Roku Ultra",
    "app_version": "1.0.1", 
    "connection_type": "wifi",
    "session_id": "uuid",
    "user_agent": "RokuOS/9.4",
    "ip_address": "192.168.1.100"
}
```

### video_metrics_daily
Pre-aggregated daily metrics for performance.

```sql
CREATE TABLE video_metrics_daily (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    video_id UUID NOT NULL REFERENCES videos(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    platform VARCHAR(50) NOT NULL,
    plays INTEGER DEFAULT 0,
    completions INTEGER DEFAULT 0,
    total_watch_time INTEGER DEFAULT 0, -- In seconds
    unique_viewers INTEGER DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    CONSTRAINT video_metrics_daily_unique 
        UNIQUE(video_id, date, platform)
);

-- Reporting indexes
CREATE INDEX idx_metrics_tenant_date ON video_metrics_daily(tenant_id, date);
CREATE INDEX idx_metrics_video_date ON video_metrics_daily(video_id, date);
CREATE INDEX idx_metrics_platform ON video_metrics_daily(platform);
```

---

## Authentication & Device Management

### sessions
Fixed session table with UUID user_id support.

```sql
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id UUID NULL, -- FIXED: Changed from BIGINT to UUID
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INTEGER NOT NULL
);

-- Session performance indexes
CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);
```

**Critical Fix Applied**: 
Migration `2025_09_11_220531_fix_sessions_user_id_uuid_type.php` changed `user_id` from `BIGINT` to `UUID` to match User model primary key type.

### device_links
OAuth2 device flow for TV app authentication.

```sql  
CREATE TABLE device_links (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    user_id UUID NULL REFERENCES users(id) ON DELETE SET NULL,
    device_code VARCHAR(255) NOT NULL, -- Long random string for API
    user_code VARCHAR(6) NOT NULL, -- 6-digit code for TV input
    verification_uri VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    device_name VARCHAR(255) NULL,
    platform VARCHAR(50) NOT NULL, -- 'roku', 'appletv', 'firetv'
    status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'verified', 'expired'
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Device linking indexes
CREATE INDEX idx_device_links_user_code ON device_links(user_code);
CREATE INDEX idx_device_links_device_code ON device_links(device_code);
CREATE INDEX idx_device_links_expires_at ON device_links(expires_at);
CREATE INDEX idx_device_links_tenant_status ON device_links(tenant_id, status);
```

### personal_access_tokens  
Laravel Sanctum API authentication tokens.

```sql
CREATE TABLE personal_access_tokens (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id UUID NOT NULL, -- UUID to match User model
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    abilities TEXT NULL, -- JSON array of scopes
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- API token indexes
CREATE INDEX idx_tokens_tokenable ON personal_access_tokens(tokenable_type, tokenable_id);
CREATE INDEX idx_tokens_token ON personal_access_tokens(token);
CREATE INDEX idx_tokens_expires_at ON personal_access_tokens(expires_at);
```

**API Token Scopes**:
- `catalog:read` - Access video catalog
- `events:write` - Submit analytics events  
- `branding:read` - Access tenant branding
- `live:read` - Access live stream data

---

## Permission System Tables

### roles
Spatie Laravel Permission role definitions.

```sql
CREATE TABLE roles (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    CONSTRAINT roles_name_guard_unique UNIQUE(name, guard_name)
);
```

### permissions
Individual permission definitions.

```sql
CREATE TABLE permissions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT, 
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    CONSTRAINT permissions_name_guard_unique UNIQUE(name, guard_name)
);
```

### model_has_roles  
User-role assignments (pivot table).

```sql
CREATE TABLE model_has_roles (
    role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    model_type VARCHAR(255) NOT NULL,
    model_id UUID NOT NULL, -- UUID to match User model
    
    PRIMARY KEY(role_id, model_id, model_type)
);

CREATE INDEX idx_model_has_roles_model ON model_has_roles(model_id, model_type);
```

**Note**: No `tenant_id` column in pivot table. For tenant-scoped roles, would need custom implementation.

---

## Schema Evolution & Migrations

### Critical Migrations Applied

1. **`2025_09_11_220531_fix_sessions_user_id_uuid_type.php`**
   - Fixed UUID/Integer mismatch in sessions table
   - Resolved authentication system failures

2. **`2025_09_11_221943_fix_playlist_items_uuid_generation.php`**
   - Added auto UUID generation for pivot table
   - Fixed playlist-video association creation

### Migration Best Practices

#### Safe Column Type Changes:
```php
// For production systems - safer approach
public function up(): void
{
    // Add new column
    Schema::table('sessions', function (Blueprint $table) {
        $table->uuid('user_id_new')->nullable()->index();
    });
    
    // Migrate data if needed
    // Update application code
    // Drop old column in subsequent migration
}
```

#### UUID Default Generation:
```php
// PostgreSQL-specific UUID generation
DB::statement('ALTER TABLE playlist_items ALTER COLUMN id SET DEFAULT gen_random_uuid()');

// Alternative for other databases
$table->uuid('id')->default(DB::raw('(UUID())'))->primary();
```

### Index Strategy

#### Performance Indexes Applied:
```sql
-- Multi-tenant query optimization
CREATE INDEX idx_videos_tenant_status ON videos(tenant_id, status);
CREATE INDEX idx_playlists_tenant_status ON playlists(tenant_id, status);

-- Analytics query optimization  
CREATE INDEX idx_events_tenant_occurred ON events(tenant_id, occurred_at);
CREATE INDEX idx_metrics_tenant_date ON video_metrics_daily(tenant_id, date);

-- Common lookup patterns
CREATE INDEX idx_device_links_user_code ON device_links(user_code);
CREATE INDEX idx_playlist_items_position ON playlist_items(playlist_id, position);
```

#### Composite Index Rationale:
- `(tenant_id, status)`: Most queries filter by both tenant and status
- `(tenant_id, occurred_at)`: Analytics queries need tenant + time range
- `(playlist_id, position)`: Video ordering within playlists

---

## Data Integrity & Constraints

### Foreign Key Relationships
```sql
-- Cascading deletes for tenant ownership
tenant_id → tenants.id ON DELETE CASCADE

-- Preserving data for analytics
video_id → videos.id ON DELETE SET NULL (in events table)

-- Maintaining referential integrity  
playlist_id → playlists.id ON DELETE CASCADE
video_id → videos.id ON DELETE CASCADE (in playlist_items)
```

### Unique Constraints
```sql
-- Tenant isolation
(email, tenant_id) -- Users can have same email across tenants
(slug, tenant_id) -- Playlist slugs unique per tenant
(external_id, tenant_id, source) -- Video IDs unique per source per tenant

-- System-wide uniqueness
subdomain -- Tenant routing
user_code -- Device pairing codes  
token -- API authentication tokens
```

---

## Multi-Tenant Security

### Global Scoping Implementation
All tenant-owned models automatically filter by `tenant_id`:

```php
// Applied automatically via HasTenantScope trait
protected static function booted()
{
    static::addGlobalScope(new TenantScope);
}
```

### Query Examples
```sql
-- Automatic tenant filtering (via global scope)
SELECT * FROM videos WHERE tenant_id = ? AND status = 'published';

-- Relationship queries maintain isolation
SELECT v.*, p.title as playlist_title 
FROM videos v
JOIN playlist_items pi ON v.id = pi.video_id
JOIN playlists p ON pi.playlist_id = p.id  
WHERE v.tenant_id = ? AND p.tenant_id = ?
ORDER BY pi.position;
```

### Data Isolation Testing
```php
// Verify tenant isolation in tests
$tenant1Video = Video::factory()->forTenant($tenant1)->create();
$tenant2User = User::factory()->forTenant($tenant2)->create();

// This should return empty collection
$videos = Video::where('tenant_id', $tenant2->id)->get();
// Confirms tenant1 video not accessible to tenant2 context
```

---

## Performance Considerations

### Query Optimization
- All tenant-scoped queries use composite indexes
- Foreign key relationships properly indexed
- JSON metadata columns indexed for common queries

### Scaling Strategy
- UUID primary keys support horizontal partitioning
- Daily metrics aggregation reduces analytics query load
- Soft deletes enable data archiving without breaking relationships

### Production Recommendations
- Monitor slow query log for missing indexes
- Consider partitioning large tables (events, metrics) by date
- Implement connection pooling for high-traffic tenants
- Regular VACUUM and ANALYZE for PostgreSQL optimization

This database schema provides a robust foundation for the Church Media Platform's multi-tenant architecture while maintaining security, performance, and data integrity.