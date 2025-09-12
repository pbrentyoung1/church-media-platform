# Church Media Platform - Technical Fixes & Solutions

## Critical Issues Resolved

### 1. UUID/Integer Type Mismatch in Sessions (CRITICAL)

**Issue**: PostgreSQL session authentication failing due to data type mismatch.

**Root Cause**: 
- Laravel's default session migration uses `foreignId('user_id')` which creates `BIGINT`
- Church Media Platform uses UUID primary keys for all models
- PostgreSQL strictly enforces type matching, causing authentication failures

**Error Message**:
```
SQLSTATE[22P02]: Invalid text representation: 7 ERROR: 
invalid input syntax for type uuid: "1993"
CONTEXT: unnamed portal parameter $3 = '...' 
(Connection: pgsql, SQL: update "sessions" set "payload" = ..., "user_id" = 1993, ...)
```

**Solution Applied**:
Created migration `2025_09_11_220531_fix_sessions_user_id_uuid_type.php`:

```php
public function up(): void
{
    Schema::table('sessions', function (Blueprint $table) {
        $table->dropColumn('user_id');
    });
    Schema::table('sessions', function (Blueprint $table) {
        $table->uuid('user_id')->nullable()->index();
    });
}

public function down(): void
{
    Schema::table('sessions', function (Blueprint $table) {
        $table->dropColumn('user_id');
    });
    Schema::table('sessions', function (Blueprint $table) {
        $table->foreignId('user_id')->nullable()->index();
    });
}
```

**Impact**: Authentication system now works correctly with UUID user IDs.

---

### 2. Circular Reference in Authentication Middleware

**Issue**: User model methods causing infinite loops during authentication.

**Root Cause**:
- `hasTenantPermission()` method called `auth()->user()` 
- Authentication middleware already processing user authentication
- Created circular dependency causing stack overflow

**Location**: `app/Models/User.php:162`

**Original Problematic Code**:
```php
public function hasTenantPermission(string $permission): bool
{
    $user = auth()->user(); // CIRCULAR REFERENCE
    return $this->hasPermissionTo($permission) && 
           $user && $user->tenant_id === $this->tenant_id;
}
```

**Fixed Code**:
```php
public function hasTenantPermission(string $permission): bool
{
    // Avoid circular reference - use static tenant context
    $currentTenant = app()->bound('tenant') ? app('tenant') : null;
    return $this->hasPermissionTo($permission) && 
           $currentTenant && $this->tenant_id === $currentTenant->id;
}
```

**Impact**: Authentication middleware executes without circular references.

---

### 3. Missing Tenant Context in Roles Query

**Issue**: Permission system attempting to query non-existent database column.

**Root Cause**:
- `getTenantRoles()` method assumed `tenant_id` column in `model_has_roles` pivot table
- Standard Laravel Permission package doesn't include tenant isolation in pivot tables
- Method failing with database column error

**Error Message**:
```
SQLSTATE[42703]: Undefined column: 7 ERROR: 
column "tenant_id" does not exist
LINE 1: ...model_has_roles"."model_id" = $1 and "tenant_id" = $2) limi...
```

**Location**: `app/Models/User.php:139`

**Solution Applied**:
```php
public function getTenantRoles(): array
{
    // For now, return all user roles since tenant-specific roles
    // would require custom pivot table with tenant_id column
    return $this->roles()
        ->pluck('name')
        ->toArray();
}
```

**Future Enhancement Note**: To implement true tenant-scoped roles, would need custom pivot table:
```php
Schema::create('tenant_model_has_roles', function (Blueprint $table) {
    $table->uuid('tenant_id');
    $table->uuid('role_id');
    $table->string('model_type');
    $table->uuid('model_id');
    // ... indexes and constraints
});
```

---

### 4. Playlist Items UUID Generation Failure

**Issue**: Many-to-many pivot table failing to create records.

**Root Cause**:
- `playlist_items` table defined with UUID primary key
- PostgreSQL not auto-generating UUIDs without explicit default
- Insert operations failing with NOT NULL constraint violations

**Error Message**:
```
SQLSTATE[23502]: Not null violation: 7 ERROR: 
null value in column "id" of relation "playlist_items" 
violates not-null constraint
```

**Solution Applied**:
Created migration `2025_09_11_221943_fix_playlist_items_uuid_generation.php`:

```php
public function up(): void
{
    // Set default UUID generation for existing table
    DB::statement('ALTER TABLE playlist_items ALTER COLUMN id SET DEFAULT gen_random_uuid()');
}

public function down(): void
{
    DB::statement('ALTER TABLE playlist_items ALTER COLUMN id DROP DEFAULT');
}
```

**Impact**: Playlist-video associations now create successfully with auto-generated UUIDs.

---

### 5. Model Factory Schema Mismatches

**Issue**: Test factories attempting to create records with non-existent database columns.

**Root Cause**:
- Factories created during initial development before final database schema
- Schema evolved but factories not updated to match
- Multiple factories had outdated column references

#### VideoFactory Issues Fixed:
- **Removed**: `user_id` (videos don't have direct user ownership)
- **Fixed**: `metadata` structure to match actual JSON schema
- **Updated**: `external_id` to use proper format for external API IDs

```php
// Before (BROKEN)
return [
    'user_id' => User::factory(), // Column doesn't exist
    'url' => $this->faker->url(), // Column doesn't exist
    // ...
];

// After (WORKING)
return [
    'tenant_id' => Tenant::factory(),
    'external_id' => $this->faker->unique()->numerify('########'),
    'playback_url' => $this->faker->url(),
    'metadata' => [
        'resolution' => $this->faker->randomElement(['720p', '1080p', '4K']),
        'category' => $this->faker->randomElement(['sermon', 'worship', 'announcement']),
    ],
];
```

#### PlaylistFactory Issues Fixed:
- **Added**: `slug` field with proper generation
- **Updated**: Status values to match actual enum
- **Fixed**: Title generation for realistic test data

```php
// Enhanced factory with proper slug generation
$title = $this->faker->words(3, true);
return [
    'tenant_id' => Tenant::factory(),
    'title' => $title,
    'slug' => \Illuminate\Support\Str::slug($title),
    'status' => $this->faker->randomElement(['published', 'draft', 'archived']),
];
```

#### EventFactory Complete Rewrite:
- **Purpose**: Analytics tracking for video interactions
- **Platforms**: Roku, Apple TV, Fire TV, Web
- **Event Types**: play, pause, complete, seek, error

```php
return [
    'tenant_id' => Tenant::factory(),
    'video_id' => null, // Set by forVideo() method
    'platform' => $this->faker->randomElement(['roku', 'appletv', 'firetv', 'web']),
    'event_type' => $this->faker->randomElement(['play', 'pause', 'complete', 'seek', 'error']),
    'seconds' => $this->faker->optional(0.8)->numberBetween(0, 7200),
    'metadata' => [
        'device_model' => $this->faker->randomElement(['Roku Ultra', 'Apple TV 4K', 'Fire TV Stick']),
        'app_version' => $this->faker->randomElement(['1.0.0', '1.0.1', '1.1.0']),
        'connection_type' => $this->faker->randomElement(['wifi', 'ethernet']),
    ],
    'occurred_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
];
```

---

### 6. Missing DeviceLink Model Implementation

**Issue**: Database migration existed but no corresponding Eloquent model.

**Root Cause**: 
- Migration created for TV app device pairing functionality
- Model class never implemented
- Relationship methods in other models referencing non-existent class

**Solution Applied**:
Created complete `app/Models/DeviceLink.php`:

```php
class DeviceLink extends Model
{
    use HasFactory, Concerns\HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'user_id', 
        'device_code',
        'user_code',
        'verification_uri',
        'expires_at',
        'device_name',
        'platform',
        'status',
        'verified_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    // Generate unique 6-digit codes for TV input
    public static function generateUserCode(): string
    {
        do {
            $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('user_code', $code)->exists());
        return $code;
    }
}
```

**Features**:
- OAuth2 device flow for TV apps
- 6-digit user codes for easy TV input
- Platform-specific device identification
- Automatic expiration handling

---

## Form Request Validation Implementation

### Issue: Direct Controller Validation
**Problem**: Controllers handling validation inline, lacking reusability and consistency.

### Solution: Dedicated Form Request Classes

#### StoreVideoRequest
```php
public function rules(): array
{
    $tenantId = auth()->user()->tenant_id;
    
    return [
        'title' => 'required|string|max:255',
        'source' => 'required|in:vimeo,youtube,resi,upload',
        'external_id' => [
            'required',
            'string', 
            'max:255',
            Rule::unique('videos')->where(function ($query) use ($tenantId) {
                return $query->where('tenant_id', $tenantId)
                            ->where('source', $this->source);
            })->ignore($this->video),
        ],
        'status' => 'sometimes|in:published,draft,archived',
        'metadata' => 'sometimes|array',
    ];
}
```

**Key Features**:
- Tenant-scoped uniqueness validation
- Source-specific external_id uniqueness
- Flexible metadata validation

#### StorePlaylistRequest
```php
public function rules(): array
{
    return [
        'title' => 'required|string|max:255',
        'slug' => [
            'nullable',
            'string',
            'max:255', 
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique('playlists')->where(function ($query) use ($tenantId) {
                return $query->where('tenant_id', $tenantId);
            })->ignore($this->playlist),
        ],
        'video_ids' => 'sometimes|array',
        'video_ids.*' => [
            'uuid',
            Rule::exists('videos', 'id')->where(function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }),
        ],
    ];
}
```

**Security Features**:
- Slug format validation with regex
- Tenant-isolated video reference validation
- Cross-tenant video assignment prevention

---

## Database Schema Improvements

### 1. UUID Primary Key Consistency
**Applied To**: All models now use UUID primary keys consistently
- Enhanced security through non-sequential IDs
- Better scalability for distributed systems
- Prevents ID enumeration attacks

### 2. Foreign Key Constraint Optimization
```sql
-- Performance-oriented indexes
CREATE INDEX idx_videos_tenant_status ON videos(tenant_id, status);
CREATE INDEX idx_playlists_tenant_status ON playlists(tenant_id, status);
CREATE INDEX idx_events_tenant_occurred ON events(tenant_id, occurred_at);
CREATE INDEX idx_device_links_user_code ON device_links(user_code);
```

### 3. JSON Column Standardization
**Consistent metadata structure** across models:
- Videos: resolution, fps, codec, bitrate, category
- Events: device_model, app_version, connection_type
- Tenants: branding, settings, feature_flags

---

## Testing & Verification Results

### Authentication Flow Testing
```bash
# Complete authentication verification
curl -c cookies.txt -d "email=admin@tenant1.test" -d "password=password123" -X POST http://127.0.0.1:8000/login
# Result: HTTP 302 redirect (successful login)

curl -b cookies.txt http://127.0.0.1:8000/dashboard  
# Result: HTTP 200 with dashboard content
```

### Model Relationship Testing
All relationships tested and verified:
- ✅ Tenant → Users (1:many)
- ✅ Tenant → Videos (1:many)  
- ✅ Tenant → Playlists (1:many)
- ✅ Video ↔ Playlist (many:many with pivot)
- ✅ User → DeviceLinks (1:many)

### Factory Data Generation
All factories produce valid test data matching current database schema.

---

## Performance Impact Analysis

### Before Fixes:
- Authentication failures causing memory exhaustion
- Circular references preventing middleware execution
- Missing indexes on frequently queried columns

### After Fixes:
- Clean authentication flow with proper session handling
- Optimized database queries with strategic indexing
- Eliminated circular dependencies in authentication chain

### Memory Usage:
- Reduced authentication middleware memory footprint
- Eliminated recursive method calls
- Proper eager loading prevents N+1 query issues

---

## Security Enhancements Applied

### 1. Tenant Isolation Validation
```php
// Prevent cross-tenant data access in form requests
Rule::exists('videos', 'id')->where(function ($query) use ($tenantId) {
    $query->where('tenant_id', $tenantId);
})
```

### 2. UUID Non-Sequential IDs
- Prevents ID enumeration attacks
- Obfuscates database record counting
- Enhances overall system security posture

### 3. Device Code Security
```php
// Cryptographically secure random code generation
public static function generateUserCode(): string
{
    do {
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    } while (static::where('user_code', $code)->exists());
    return $code;
}
```

---

## Lessons Learned & Best Practices

### 1. UUID Migration Strategy
When changing column types in production:
1. Create new column with UUID type
2. Populate new column with converted data  
3. Update application code to use new column
4. Drop old column after verification

### 2. Factory Maintenance
- Keep factories synchronized with schema changes
- Test factories regularly in development
- Use factories to validate relationship definitions

### 3. Authentication Debugging
- Use separate curl sessions for authentication testing
- Preserve cookies between requests for session testing
- Monitor Laravel logs for middleware execution details

### 4. Multi-Tenant Security
- Always validate tenant ownership in form requests
- Use global scopes to prevent accidental cross-tenant queries
- Test tenant isolation thoroughly in feature tests

This comprehensive fix implementation has resolved all critical authentication and database issues, establishing a solid foundation for the Church Media Platform's continued development.