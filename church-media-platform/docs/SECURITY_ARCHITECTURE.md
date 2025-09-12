# Security Architecture Documentation

## Multi-Tenant Security Framework

### Core Security Principles

1. **Defense in Depth**: Multiple layers of security controls
2. **Principle of Least Privilege**: Minimal required permissions
3. **Zero Trust Architecture**: Verify every request and user
4. **Data Isolation**: Complete tenant separation at all levels

## Tenant Isolation Strategy

### Database Level Security

#### Global Eloquent Scopes
**Purpose**: Automatic tenant filtering on all queries

```php
// Applied to all tenant-owned models
protected static function booted(): void
{
    static::addGlobalScope('tenant', function ($query) {
        if (auth()->check() && auth()->user()->tenant_id) {
            $query->where('tenant_id', auth()->user()->tenant_id);
        }
    });

    static::creating(function ($model) {
        if (auth()->check() && !$model->tenant_id) {
            $model->tenant_id = auth()->user()->tenant_id;
        }
    });
}
```

**Protection Against**:
- Cross-tenant data access
- Data leakage between organizations
- Accidental tenant mixing

#### Row Level Security (Future Enhancement)
**PostgreSQL RLS Implementation**:
```sql
-- Enable RLS on tenant tables
ALTER TABLE videos ENABLE ROW LEVEL SECURITY;
ALTER TABLE playlists ENABLE ROW LEVEL SECURITY;

-- Create policies for tenant isolation
CREATE POLICY tenant_isolation ON videos 
FOR ALL TO app_user 
USING (tenant_id = current_setting('app.current_tenant_id')::uuid);
```

### Authentication Security

#### Multi-Factor Authentication (2FA)

**TOTP Implementation**:
- **Algorithm**: Time-based One-Time Password (RFC 6238)
- **Secret Storage**: Encrypted in database using Laravel's encryption
- **QR Code**: Generated for authenticator app setup
- **Recovery Codes**: 8 single-use backup codes, encrypted storage
- **Enforcement**: Required for admin roles, configurable per tenant

**2FA Flow**:
1. User enables 2FA → generates secret
2. QR code displayed for authenticator app
3. User confirms with 6-digit code
4. Recovery codes generated and shown once
5. 2FA required for subsequent logins

#### Session Management

**Security Configuration**:
```php
// Secure session settings
'lifetime' => 120,           // 2 hours max
'expire_on_close' => true,   // Close browser = logout
'encrypt' => true,           // Encrypt session data
'http_only' => true,         // No JavaScript access
'secure' => true,            // HTTPS only
'same_site' => 'lax',        // CSRF protection
```

**Session Security Features**:
- **Automatic regeneration**: New session ID every 30 minutes
- **Tenant validation**: Session tied to specific tenant
- **Device tracking**: Monitor session creation across devices
- **Concurrent session limits**: Maximum active sessions per user

### API Security Framework

#### Token-Based Authentication

**Personal Access Tokens (PAT)**:
- **Scoped permissions**: `catalog:read`, `events:write`, etc.
- **Expiration**: 90-day maximum for TV devices
- **Minimal scope**: Only necessary permissions granted
- **Revocable**: Admin can revoke any token instantly

**Token Scopes**:
```php
// TV Device Tokens
'catalog:read'    => 'Read video catalog and playlists',
'events:write'    => 'Submit analytics events',

// Admin API Tokens  
'admin:full'      => 'Full administrative access',
'content:manage'  => 'Manage videos and playlists',
'users:manage'    => 'Manage organization users',
```

#### Device Linking Security

**6-Digit Code Flow**:
1. **TV generates**: Device code + user code (6 digits)
2. **User visits**: /link with fresh 2FA verification required
3. **Admin approves**: After verifying 6-digit code
4. **Token issued**: Scoped PAT with 90-day expiration
5. **Audit logged**: Device approval recorded

**Security Controls**:
- **Code expiration**: 10-minute TTL for user codes
- **Fresh 2FA required**: Must re-authenticate within 15 minutes
- **Rate limiting**: 5 attempts per device per hour
- **Audit trail**: All device approvals logged

### Input Validation & Data Protection

#### Request Validation

**FormRequest Classes**:
```php
class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::exists('users')->where('tenant_id', $this->tenant->id)
            ],
            'password' => [
                'required',
                'string',
                'min:12',
                'max:255'
            ]
        ];
    }
}
```

**Validation Rules**:
- **Email format**: RFC compliant with DNS verification
- **Password strength**: Minimum 12 characters, complexity requirements
- **Tenant context**: Validate user belongs to requesting tenant
- **Rate limiting**: Progressive delays on failed attempts

#### SQL Injection Prevention

**Prepared Statements**: All queries use parameter binding
**Query Builder**: Laravel's query builder prevents SQL injection
**Raw Queries**: Avoided; when necessary, parameters are bound
**Global Scopes**: Automatic tenant filtering prevents bypass attempts

#### XSS Protection

**Content Security Policy**:
```php
$csp = "default-src 'self'; " .
       "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
       "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
       "img-src 'self' data: https:; " .
       "font-src 'self' https://fonts.gstatic.com;";
```

**Input Sanitization**:
- **HTML entities**: Automatic escaping in Blade/Vue templates
- **JSON responses**: Proper encoding prevents script injection
- **File uploads**: Validation, sanitization, and secure storage

### Network Security

#### HTTPS Enforcement

**Security Headers**:
```php
'X-Frame-Options' => 'DENY',
'X-Content-Type-Options' => 'nosniff', 
'X-XSS-Protection' => '1; mode=block',
'Referrer-Policy' => 'strict-origin-when-cross-origin',
'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'
```

#### CORS Configuration

**Allowed Origins**:
- `admin.tech.forworship.org` (production admin)
- `staging.tech.forworship.org` (staging environment)
- `localhost:*` (development only)

**Restricted Methods**: Only necessary HTTP methods allowed per endpoint

#### Rate Limiting

**Endpoint-Specific Limits**:
```php
'login' => '5 per minute',           // Login attempts
'two-factor' => '5 per minute',      // 2FA verification
'api-catalog' => '1000 per minute',  // TV catalog requests
'api-analytics' => '500 per minute', // Analytics events
'device-link' => '10 per hour',      // Device linking
```

### Data Security

#### Encryption at Rest

**Database Encryption**:
- **Sensitive fields**: `two_factor_secret`, `two_factor_recovery_codes`
- **Encryption method**: AES-256-CBC via Laravel's encryption
- **Key rotation**: Supported via APP_KEY rotation

**File Storage Encryption**:
- **User uploads**: Encrypted before storage
- **Backup files**: GPG encryption for database backups
- **Logs**: Sensitive data removed/masked before logging

#### Encryption in Transit

**TLS Configuration**:
- **Minimum version**: TLS 1.2
- **Cipher suites**: Strong encryption only
- **Certificate validation**: Proper SSL/TLS certificates
- **HSTS headers**: Force HTTPS connections

### Access Control

#### Role-Based Access Control (RBAC)

**Roles Hierarchy**:
```
church-admin     -> All permissions
content-manager  -> Content and playlist management
group-leader     -> Limited content access
viewer          -> Read-only access
```

**Permission Matrix**:
```php
'manage-church-settings' => ['church-admin'],
'upload-videos'         => ['church-admin', 'content-manager'],
'manage-playlists'      => ['church-admin', 'content-manager'],
'view-analytics'        => ['church-admin', 'content-manager'],
'manage-users'          => ['church-admin'],
'publish-roku-channel'  => ['church-admin'],
```

#### Policy Authorization

**Model Policies**:
```php
class VideoPolicy
{
    public function view(User $user, Video $video): bool
    {
        return $user->tenant_id === $video->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('upload-videos') &&
               $user->tenant->isActive();
    }
}
```

### Audit & Monitoring

#### Security Event Logging

**Logged Events**:
- **Authentication**: Login attempts, 2FA setup/disable, password changes
- **Authorization**: Permission denials, role changes, unauthorized access
- **Data Access**: Cross-tenant access attempts, bulk operations
- **API Usage**: Token creation/revocation, rate limit violations
- **Device Management**: Device linking, token approvals

**Log Format**:
```json
{
    "timestamp": "2025-01-15T10:30:00Z",
    "event_type": "authentication_failure", 
    "tenant_id": "uuid",
    "user_id": "uuid",
    "ip_address": "192.168.1.1",
    "user_agent": "Mozilla/5.0...",
    "details": {
        "reason": "invalid_password",
        "attempt_count": 3
    }
}
```

#### Intrusion Detection

**Automated Monitoring**:
- **Failed login tracking**: Progressive delays, account lockout
- **Unusual access patterns**: Geographic, time-based anomalies
- **API abuse detection**: Rate limit violations, token misuse
- **Cross-tenant attempts**: Immediate alerts and blocking

### Incident Response

#### Security Incident Procedures

**Detection**:
- **Automated alerts**: Failed authentication, cross-tenant access
- **Manual reporting**: User reports, support tickets
- **Monitoring dashboards**: Real-time security metrics

**Response Steps**:
1. **Immediate**: Isolate affected accounts/tenants
2. **Investigation**: Analyze logs, determine scope
3. **Containment**: Block malicious IPs, revoke compromised tokens
4. **Recovery**: Reset passwords, regenerate secrets
5. **Post-incident**: Update security measures, document lessons

#### Data Breach Protocol

**Notification Requirements**:
- **Internal**: Security team notified within 1 hour
- **Customer**: Affected churches notified within 24 hours
- **Regulatory**: Compliance with applicable data protection laws

**Remediation Actions**:
- **Password resets**: Force password changes for affected users
- **Token revocation**: Invalidate all API tokens
- **2FA re-enrollment**: Require fresh 2FA setup
- **Security review**: Complete security audit and updates

### Security Testing

#### Regular Security Assessments

**Automated Testing**:
- **SAST**: Static code analysis for vulnerabilities
- **DAST**: Dynamic testing of running application
- **Dependency scanning**: Check for vulnerable packages
- **Infrastructure scanning**: Server and network security

**Manual Testing**:
- **Penetration testing**: Quarterly external security assessment
- **Code review**: Security-focused code reviews
- **Social engineering**: Staff security awareness testing

#### Vulnerability Management

**Patch Management**:
- **Critical vulnerabilities**: Patched within 24 hours
- **High severity**: Patched within 1 week
- **Medium/Low**: Patched in regular release cycle

**Disclosure Policy**:
- **Responsible disclosure**: Clear process for security researchers
- **Bug bounty**: Incentivized vulnerability reporting
- **Public disclosure**: Coordinated after fix deployment

---

**Security Framework Version**: 1.0  
**Last Security Review**: Sprint 1 completion  
**Next Review**: After authentication implementation  
**Compliance**: GDPR, CCPA, SOC 2 Type II (planned)
