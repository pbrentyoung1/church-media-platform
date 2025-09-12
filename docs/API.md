# API Reference

## New Node.js Architecture (v2)

### JWT Authentication
All API endpoints use JWT authentication with refresh token rotation.

**Authentication Flow:**
```typescript
// Login response
{
  "access_token": "jwt-token",
  "refresh_token": "refresh-jwt-token", 
  "expires_in": 900, // 15 minutes
  "token_type": "Bearer"
}
```

**Headers Required:**
```
Authorization: Bearer {jwt-token}
Content-Type: application/json
```

**Tenant Resolution:**
- Automatic tenant resolution via subdomain (`{tenant}.app.churchmedia.com`)
- No X-Tenant header required (improved security)

### Rate Limiting (Enhanced)
- **API Endpoints**: 120 requests per minute per tenant
- **Authentication**: 5 attempts per minute per IP with exponential backoff
- **Media Operations**: 10 requests per minute per endpoint

### Video Source Management (Phased Approach)

**Phase 1 - External Sources (Current):**
- Vimeo integration (import existing videos)
- Resi integration (live streaming)
- YouTube integration (future)

**Phase 2 - Direct Uploads (Future):**
- Dedicated media server required
- S3-compatible storage
- Video transcoding pipeline
- CDN distribution

## Node.js API Endpoints (v2)

### Authentication Endpoints
```typescript
POST /api/auth/login          // User login
POST /api/auth/refresh        // Token refresh
POST /api/auth/logout         // Logout
POST /api/auth/2fa/setup      // Setup TOTP 2FA
POST /api/auth/2fa/verify     // Verify TOTP code
```

### Video Management
```typescript
GET    /api/videos            // List videos with filtering
POST   /api/videos            // Create video metadata
GET    /api/videos/:id        // Get single video
PUT    /api/videos/:id        // Update video
DELETE /api/videos/:id        // Delete video
POST   /api/videos/import/vimeo  // Import from Vimeo
POST   /api/videos/import/resi   // Import from Resi
// POST   /api/videos/upload       // Direct upload (Phase 2)
```

### Playlist Management
```typescript
GET    /api/playlists         // List playlists
POST   /api/playlists         // Create playlist
GET    /api/playlists/:id     // Get single playlist
PUT    /api/playlists/:id     // Update playlist
DELETE /api/playlists/:id     // Delete playlist
PUT    /api/playlists/:id/videos  // Update video order
```

### Analytics & Events
```typescript
GET    /api/analytics/dashboard    // Overview stats
GET    /api/analytics/videos/:id   // Video-specific metrics
POST   /api/events               // Track viewing events
```

### Tenant Management
```typescript
GET    /api/tenant/profile        // Tenant settings
PUT    /api/tenant/profile        // Update tenant
GET    /api/tenant/branding       // Branding config
PUT    /api/tenant/branding       // Update branding
```

### Device Linking (Roku/TV Apps)
```typescript
POST   /api/device/link/start     // Generate device codes
POST   /api/device/link/verify    // Verify device
GET    /api/device/links          // List linked devices
DELETE /api/device/links/:id      // Unlink device
```

### Public API (Roku Apps)
```typescript
GET    /api/v1/catalog           // Video catalog for tenant
GET    /api/v1/branding          // Branding for apps
GET    /api/v1/live             // Live stream data
POST   /api/v1/events           // Analytics from devices
```

### Enhanced Features (v2 Only)
```typescript
GET    /api/health               // API health check
GET    /api/docs                 // OpenAPI documentation
GET    /api/metrics              // Tenant usage metrics
```

---

## Legacy Laravel API (v1 - Preserved for Reference)

### Bearer Tokens (Laravel Sanctum)
All API endpoints require authentication via Bearer tokens. Tokens must include appropriate scopes.

**Available Scopes:**
- `catalog:read` - Access video catalog and search
- `events:write` - Create and manage events
- `branding:read` - Access church branding configuration
- `live:read` - Access live stream data

**Headers Required:**
```
Authorization: Bearer {token}
X-Tenant: {tenant-id}
Accept: application/json
```

### Rate Limiting
- **API Endpoints**: 60 requests per minute per token
- **Authentication**: 5 failed attempts per minute per IP

## Core Endpoints

### Catalog API
**GET /api/v1/catalog**

Retrieves tenant-specific video catalog with pagination and filtering.

**Parameters:**
- `per_page` (int, optional): Items per page (default: 15, max: 100)
- `page` (int, optional): Page number (default: 1)
- `category` (string, optional): Filter by category
- `status` (string, optional): Filter by status (published, draft)

**Response Format:**
```json
{
  "data": [
    {
      "id": "uuid",
      "tenant_id": "uuid",
      "title": "Video Title",
      "description": "Video description",
      "video_url": "https://...",
      "thumbnail_url": "https://...",
      "duration": 3600,
      "status": "published",
      "visibility": "public",
      "category": "sermon",
      "published_at": "2024-01-01T00:00:00Z",
      "metadata": {...}
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

**Performance:** <200ms response time tested with 1000+ videos

---

### Search API
**GET /api/v1/search**

Full-text search across video titles and descriptions.

**Parameters:**
- `q` (string, required): Search query
- `per_page` (int, optional): Results per page (default: 15, max: 50)

**Response:** Same format as catalog API

**Performance:** <200ms response time tested with 500+ videos

---

### Branding API
**GET /api/v1/branding**

Retrieves tenant-specific branding configuration for Roku apps.

**Response Format:**
```json
{
  "church_name": "My Church",
  "primary_color": "#4f46e5",
  "logo_url": "https://...",
  "roku_theme_json": {
    "primary": "#4f46e5",
    "secondary": "#ffffff",
    "accent": "#10b981"
  }
}
```

**Performance:** <50ms response time with caching

---

### Events API
**POST /api/v1/events**

Creates new events (requires `events:write` scope).

**Request Body:**
```json
{
  "title": "Event Title",
  "description": "Event description",
  "start_time": "2024-01-01T10:00:00Z",
  "end_time": "2024-01-01T11:00:00Z",
  "type": "live_stream",
  "metadata": {...}
}
```

**Response:** Created event object with 201 status

---

### Live Stream API
**GET /api/v1/live**

Retrieves active live streams for tenant.

**Response Format:**
```json
{
  "data": [
    {
      "id": "uuid",
      "title": "Live Service",
      "status": "live",
      "stream_url": "https://...",
      "viewer_count": 150,
      "started_at": "2024-01-01T10:00:00Z"
    }
  ]
}
```

## Security & Tenant Isolation

### Tenant Isolation
- All endpoints automatically scope to the authenticated user's tenant
- Cross-tenant access returns 403 Forbidden
- SQL injection prevention through Eloquent ORM and parameterized queries

### Token Scoping
- Tokens are scoped to specific permissions
- Invalid scope access returns 403 Forbidden
- Token manipulation attempts return 401 Unauthorized

### Security Headers
All API responses include security headers:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`

## Error Responses

### Standard Error Format
```json
{
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### Common Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized (invalid/missing token)
- `403` - Forbidden (insufficient permissions/wrong tenant)
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests (rate limited)
- `500` - Internal Server Error

## Testing Coverage

The API endpoints have comprehensive test coverage including:
- **Functional Tests**: All endpoint behaviors validated
- **Security Tests**: CSRF, SQL injection, cross-tenant access prevention
- **Performance Tests**: All endpoints validated <200ms response time
- **Rate Limiting Tests**: Brute force protection validated
- **Tenant Isolation Tests**: 100% cross-tenant access prevention
- **Integration Tests**: Complete workflow validation

**Test Suite:** 80+ individual tests across all endpoints and security scenarios
