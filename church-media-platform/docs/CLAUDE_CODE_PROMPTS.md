# Claude Code Development Prompts

## Authentication Foundation Implementation

### Prompt 1: Configure Sanctum and Fortify
```
Configure Laravel Sanctum for SPA authentication and Laravel Fortify for 2FA in a multi-tenant church media platform:

1. Publish Sanctum and Fortify configurations if not already done
2. Update config/sanctum.php to support admin.tech.forworship.org domain
3. Configure config/fortify.php with:
   - Disabled views (SPA mode)
   - Enable 2FA, password reset, email verification, profile updates
   - Set home path to /dashboard
   - Configure rate limiting for login and 2FA

4. Update .env with:
   - SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,admin.tech.forworship.org
   - FORTIFY_FEATURES as needed

5. Add Sanctum middleware to api routes in bootstrap/app.php
6. Ensure HasApiTokens trait is in User model (already added)
```

### Prompt 2: Create Tenant-Aware Authentication Middleware
```
Create middleware for tenant-aware authentication in the multi-tenant church platform:

1. Create TenantContext middleware that:
   - Extracts tenant from subdomain or header
   - Sets tenant context for the request
   - Validates user belongs to the tenant
   - Stores tenant in app container for global access

2. Create RequiresTenantAccess middleware that:
   - Ensures authenticated user has access to the requested tenant
   - Prevents cross-tenant data access
   - Returns 403 for unauthorized tenant access

3. Create Requires2FA middleware that:
   - Checks if 2FA is enabled for the user's tenant
   - Enforces 2FA for admin roles
   - Redirects to 2FA setup if required but not configured

4. Register all middleware in bootstrap/app.php
5. Apply appropriate middleware to route groups
```

### Prompt 3: Create Authentication Controllers
```
Create authentication controllers for the multi-tenant church media platform using Inertia.js:

1. Create LoginController that:
   - Handles tenant-aware login
   - Validates tenant context before authentication
   - Returns appropriate Inertia responses
   - Handles failed login attempts with rate limiting

2. Create TwoFactorController that:
   - Handles 2FA setup with QR code generation
   - Confirms 2FA with TOTP verification
   - Manages recovery codes
   - Provides 2FA challenge/verify endpoints

3. Create DashboardController with tenant-specific dashboard

4. All controllers should:
   - Use FormRequest validation classes
   - Apply tenant isolation
   - Return Inertia responses for SPA
   - Include proper error handling and security
```

### Prompt 4: Set Up Inertia.js with Vue 3 and Authentication Pages
```
Set up Inertia.js with Vue 3 for the church media platform admin interface:

1. Configure Inertia.js properly:
   - Update app.js for Vue 3 + Inertia
   - Configure Inertia SSR head management
   - Set up CSRF token handling
   - Configure progress indicators

2. Create Vue 3 authentication components:
   - Login.vue with tenant-aware form
   - TwoFactorChallenge.vue for 2FA verification
   - TwoFactorSetup.vue with QR code display
   - Dashboard.vue with tenant branding

3. Create shared layout components:
   - AppLayout.vue with tenant branding support
   - AuthLayout.vue for login pages
   - Navigation component with user menu

4. Configure routing and middleware
5. Add Bootstrap 5 styling integration
6. Test authentication flow works end-to-end
```

### Prompt 5: Create API Routes for Roku Integration
```
Create API routes for Roku channel integration in the church media platform:

1. Create API controllers:
   - CatalogController for video/playlist feeds
   - DeviceLinkController for TV authentication
   - AnalyticsController for event tracking
   - BrandingController for tenant theme configs

2. Set up API routes under /api/v1/ with:
   - Public catalog endpoints with rate limiting
   - Device linking flow (6-digit codes)
   - Authenticated analytics endpoints
   - Tenant-scoped branding endpoints

3. Implement API authentication:
   - Personal access tokens for TV devices
   - Scoped permissions (catalog:read, events:write)
   - Token expiration handling
   - Signed analytics requests

4. Create API resources/transformers for:
   - Video catalog JSON for Roku
   - Playlist structures
   - Branding configurations
   - Error responses

5. Add comprehensive API documentation
```

### Prompt 6: Comprehensive Testing Suite
```
Create comprehensive tests for the multi-tenant church media platform authentication system:

1. **Feature Tests for Authentication Flow:**
   - Test tenant-aware login with valid/invalid credentials
   - Test cross-tenant access prevention (user from tenant A cannot access tenant B)
   - Test 2FA setup flow with QR code generation and TOTP verification
   - Test 2FA enforcement for admin users vs optional for editors
   - Test recovery code generation and usage
   - Test password reset flow with tenant context
   - Test session management and logout

2. **API Tests for Roku Integration:**
   - Test device linking flow (init, poll, approve, token generation)
   - Test catalog API with tenant isolation (tenant A cannot see tenant B videos)
   - Test analytics event submission with signed requests
   - Test branding API returns correct tenant configuration
   - Test rate limiting on public API endpoints
   - Test token scoping (catalog:read vs events:write permissions)

3. **Unit Tests for Core Components:**
   - Test TenantContext middleware with various subdomain/header scenarios
   - Test global tenant scopes prevent cross-tenant data leakage
   - Test User model tenant relationships and permissions
   - Test Tenant model branding configuration generation
   - Test Video/Playlist models with tenant isolation

4. **Database Tests:**
   - Test all migrations run successfully on fresh database
   - Test seeder creates demo tenant with proper roles/permissions
   - Test UUID handling in permission system works correctly
   - Test foreign key constraints prevent orphaned records

5. **Integration Tests:**
   - Test complete login flow from Vue component to backend
   - Test 2FA setup flow end-to-end with QR code scanning simulation
   - Test device linking from Roku channel perspective
   - Test tenant branding loads correctly in admin interface
   - Test video catalog API consumed by mock Roku channel

6. **Security Tests:**
   - Test CSRF protection on all authenticated routes
   - Test SQL injection prevention in tenant scopes
   - Test unauthorized tenant access returns 403
   - Test 2FA bypass attempts fail
   - Test API token manipulation attempts fail
   - Test rate limiting prevents abuse

7. **Browser Tests (if applicable):**
   - Test login flow in actual browser
   - Test 2FA setup with real authenticator app
   - Test dashboard loads with tenant branding
   - Test navigation and logout work correctly

8. **Test Data Setup:**
   - Create additional test tenants and users for comprehensive testing
   - Create sample videos and playlists for API testing
   - Set up mock Vimeo API responses for integration tests

Run all tests and ensure 100% pass rate before proceeding to Step 2C (Basic Admin Interface). Include performance benchmarks for API endpoints to ensure they meet the <200ms requirement.
```

## Usage Instructions

1. **Execute prompts sequentially** - Each builds on the previous
2. **Test after each prompt** - Validate functionality before proceeding
3. **Commit frequently** - Use conventional commits for each completed prompt
4. **Document issues** - Note any deviations or problems encountered
5. **Performance check** - Ensure API responses meet <200ms requirement

## Expected Outcomes

After completing all prompts:
- ✅ Complete SPA authentication with tenant isolation
- ✅ TOTP 2FA with QR codes and recovery codes
- ✅ Tenant-aware middleware stack
- ✅ Vue 3 authentication interface
- ✅ API endpoints ready for Roku integration
- ✅ Comprehensive test coverage
- ✅ Production-ready authentication foundation

## Success Criteria

- All tests pass with 100% success rate
- Authentication flow works end-to-end
- Cross-tenant isolation verified
- API performance meets requirements (<200ms)
- Security audit passes (no vulnerabilities)
- Ready for content management development (Step 2C)

---

**Prompt Version**: 1.0  
**Target Phase**: Authentication Foundation (Step 2B)  
**Prerequisites**: Multi-tenant database schema completed
