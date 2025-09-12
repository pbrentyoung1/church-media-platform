# Subdomain Strategy v2 - Enhanced Multi-Level Architecture

## Overview

The new subdomain strategy provides better scalability, security, and performance through dedicated subdomains for different services and improved tenant isolation.

## Current Architecture (v2 - Node.js)

### Primary Subdomains

```
{tenant}.media.forworship.org     # Primary tenant application
{tenant}.api.media.forworship.org # Optional dedicated API endpoint  
admin.media.forworship.org        # Platform administration
api.media.forworship.org          # Shared API services
```

### Service-Specific Subdomains

```
docs.media.forworship.org         # API documentation (OpenAPI/Swagger)
status.media.forworship.org       # System status and health monitoring
cdn.media.forworship.org          # CDN and media delivery
analytics.media.forworship.org    # Analytics and metrics dashboard
```

### ForWorship Ecosystem

```
forworship.org                    # Main ForWorship website
media.forworship.org              # Church Media Platform (this app)
plan.forworship.org               # Future planning tool
create.forworship.org             # Future creation tool
```

## Benefits of New Structure

### 1. **Scalability**
- **Load Balancer Configuration**: Easier to configure different load balancing rules per service
- **CDN Integration**: Dedicated CDN subdomain for optimized media delivery
- **Service Isolation**: Each service can be scaled independently
- **Geographic Distribution**: Different services can be deployed to different regions

### 2. **Security** 
- **API Isolation**: Separate API domains prevent cross-service attacks
- **CORS Configuration**: More granular control over cross-origin requests
- **Certificate Management**: Individual SSL certificates per service
- **Rate Limiting**: Service-specific rate limiting policies

### 3. **Performance**
- **DNS Resolution**: Faster DNS resolution with service-specific records
- **Caching**: Different caching strategies per service type
- **Connection Pooling**: Optimized connection handling per service
- **HTTP/2 Push**: Service-specific resource pushing

### 4. **Development & Operations**
- **Environment Separation**: Clear separation between staging and production
- **Service Discovery**: Easier service discovery and routing
- **Monitoring**: Service-specific monitoring and alerting
- **Deployment**: Independent deployment per service

## Implementation Details

### Tenant Resolution Logic

```typescript
// Automatic tenant resolution from subdomain
const extractTenantFromHost = (host: string): string | null => {
  const subdomain = host.split('.')[0];
  
  // Skip service subdomains
  const serviceSubdomains = ['admin', 'api', 'docs', 'status', 'cdn', 'analytics'];
  if (serviceSubdomains.includes(subdomain)) {
    return null; // Not a tenant subdomain
  }
  
  // Extract tenant from app subdomain
  if (host.includes('.app.churchmedia.com')) {
    return subdomain;
  }
  
  // Handle custom domains
  return resolveTenantFromCustomDomain(host);
};
```

### Custom Domain Support

```
# Custom domain mapping
media.firstchurch.org     → {tenant-id}.media.forworship.org
videos.mycoolchurch.com   → {tenant-id}.media.forworship.org
live.awesomechurch.net    → {tenant-id}.media.forworship.org
```

### Database Schema for Custom Domains

```prisma
model Tenant {
  id           String   @id @default(cuid())
  name         String
  subdomain    String   @unique
  customDomain String?  @unique
  // ... other fields
}

model CustomDomain {
  id           String   @id @default(cuid())
  domain       String   @unique
  tenantId     String
  verified     Boolean  @default(false)
  verifiedAt   DateTime?
  
  tenant       Tenant   @relation(fields: [tenantId], references: [id])
}
```

## Environment Configuration

### Development Environment
```
# Local development
{tenant}.media.localhost:3000      # Primary app
{tenant}.api.media.localhost:3001  # API endpoint
admin.media.localhost:3000         # Admin interface
```

### Staging Environment
```
{tenant}.media.staging.forworship.org
{tenant}.api.media.staging.forworship.org
admin.media.staging.forworship.org
api.media.staging.forworship.org
```

### Production Environment
```
{tenant}.media.forworship.org
{tenant}.api.media.forworship.org     # Optional for high-traffic tenants
admin.media.forworship.org
api.media.forworship.org
```

## Load Balancer Configuration

### Service-Specific Routing

```nginx
# Primary application
server {
    server_name ~^(?<tenant>[^.]+)\.app\.churchmedia\.com$;
    location / {
        proxy_pass http://app-servers;
        proxy_set_header X-Tenant $tenant;
    }
}

# API endpoints  
server {
    server_name ~^(?<tenant>[^.]+)\.api\.churchmedia\.com$;
    location / {
        proxy_pass http://api-servers;
        proxy_set_header X-Tenant $tenant;
    }
}

# Shared API services
server {
    server_name api.churchmedia.com;
    location / {
        proxy_pass http://shared-api-servers;
    }
}

# Admin interface
server {
    server_name admin.churchmedia.com;
    location / {
        proxy_pass http://admin-servers;
    }
}
```

## DNS Configuration

### Primary DNS Records

```dns
; Primary application subdomains
*.app.churchmedia.com.     300  IN  CNAME  app-lb.churchmedia.com.
*.api.churchmedia.com.     300  IN  CNAME  api-lb.churchmedia.com.

; Service subdomains
admin.churchmedia.com.     300  IN  CNAME  admin-lb.churchmedia.com.
api.churchmedia.com.       300  IN  CNAME  shared-api-lb.churchmedia.com.
docs.churchmedia.com.      300  IN  CNAME  docs-lb.churchmedia.com.
status.churchmedia.com.    300  IN  CNAME  status-lb.churchmedia.com.

; CDN and media
cdn.churchmedia.com.       300  IN  CNAME  cdn.provider.com.
```

## Security Considerations

### SSL Certificate Management
```yaml
# Wildcard certificates for each subdomain level
certificates:
  - "*.app.churchmedia.com"      # Tenant applications
  - "*.api.churchmedia.com"      # Tenant APIs  
  - "churchmedia.com"            # Root domain
  - "*.churchmedia.com"          # Service subdomains
```

### CORS Configuration
```typescript
const corsOptions = {
  origin: (origin, callback) => {
    // Allow app subdomains to access API subdomains
    if (origin?.match(/^https:\/\/[\w-]+\.app\.churchmedia\.com$/)) {
      return callback(null, true);
    }
    
    // Allow admin to access shared API
    if (origin === 'https://admin.churchmedia.com') {
      return callback(null, true);
    }
    
    callback(new Error('Not allowed by CORS'));
  }
};
```

## Migration from Legacy Subdomains

### Current Laravel Strategy (v1)
```
{tenant}.churchmedia.com         # Legacy format
```

### Migration Path
1. **Phase 1**: Support both legacy and new formats
2. **Phase 2**: Redirect legacy to new format  
3. **Phase 3**: Deprecate legacy format (with notice)
4. **Phase 4**: Remove legacy support

### Redirect Configuration
```nginx
# Redirect legacy subdomains to new format
server {
    server_name ~^(?<tenant>[^.]+)\.churchmedia\.com$;
    return 301 https://$tenant.app.churchmedia.com$request_uri;
}
```

## Monitoring and Analytics

### Service-Specific Metrics
- **App Performance**: Response times per tenant application
- **API Performance**: Endpoint response times and error rates
- **Custom Domain Health**: SSL certificate status and DNS resolution
- **Geographic Performance**: Response times by region

### Health Check Endpoints
```
https://admin.churchmedia.com/health
https://api.churchmedia.com/health  
https://status.churchmedia.com/api/status
```

## Future Considerations

### Regional Expansion
```
# Geographic distribution
{tenant}.app.us.churchmedia.com      # US region
{tenant}.app.eu.churchmedia.com      # Europe region
{tenant}.app.asia.churchmedia.com    # Asia region
```

### Service Mesh Integration
- **Istio/Envoy**: Service-to-service communication
- **Circuit Breakers**: Fault tolerance between services
- **Traffic Splitting**: A/B testing and gradual rollouts

This enhanced subdomain strategy provides a robust foundation for scaling the Church Media Platform while maintaining security, performance, and operational flexibility.