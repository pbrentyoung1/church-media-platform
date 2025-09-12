# ForWorship.org Infrastructure Setup Documentation

## Domain & Subdomain Mapping

### Production Domains
```
forworship.org                    → /public_html          (Main marketing site)
tech.forworship.org              → /tech-site             (Tech/developer site)
admin.tech.forworship.org        → /admin/current/public  (Church admin CMS)
api.tech.forworship.org          → /api/current/public    (API endpoints)
staging.tech.forworship.org      → /staging/current/public (Staging environment)
docs.tech.forworship.org         → /docs                  (Documentation)
```

## Directory Structure

### Server File System Layout
```
/public_html/
├── public_html/                  ← forworship.org (main site)
├── tech-site/                    ← tech.forworship.org (tech site)
├── docs/                         ← docs.tech.forworship.org (documentation)
├── admin/
│   └── current/                  ← Git repository for admin interface
│       ├── app/
│       ├── config/
│       ├── database/
│       ├── public/               ← admin.tech.forworship.org points here
│       ├── storage/
│       ├── .env
│       ├── .cpanel.yml
│       └── composer.json
├── api/
│   └── current/                  ← Git repository for API (future)
│       └── public/               ← api.tech.forworship.org points here
└── staging/
    └── current/                  ← Git repository for staging
        └── public/               ← staging.tech.forworship.org points here
```

## Git Repository Configuration

### Admin Interface (Current Active Deployment)
- **Repository Path:** `/admin/current`
- **Repository Name:** `church-media-platform`
- **Clone URL:** `https://github.com/pbrentyoung1/church-media-platform.git`
- **Branch:** `main` (production)
- **Document Root:** `/admin/current/public/` (automatically mapped)
- **Accessible URL:** `https://admin.tech.forworship.org`

### API Deployment (Future)
- **Repository Path:** `/api/current`
- **Repository Name:** `church-media-platform`
- **Clone URL:** `https://github.com/pbrentyoung1/church-media-platform.git`
- **Branch:** `main` or `api-only` 
- **Document Root:** `/api/current/public/`
- **Accessible URL:** `https://api.tech.forworship.org`

### Staging Environment
- **Repository Path:** `/staging/current`
- **Repository Name:** `church-media-platform`
- **Clone URL:** `https://github.com/pbrentyoung1/church-media-platform.git`
- **Branch:** `staging` (auto-deploy from staging branch)
- **Document Root:** `/staging/current/public/`
- **Accessible URL:** `https://staging.tech.forworship.org`

## Development Workflow

### Git Branch Strategy
```
Local Development:
feature/new-feature → staging branch → staging.tech.forworship.org (auto-deploy)
staging branch → main branch → admin.tech.forworship.org (production)
```

### Deployment Process
1. **Development:** Work in feature branches locally
2. **Staging:** Merge feature → staging branch → auto-deploys to staging.tech.forworship.org
3. **Production:** Merge staging → main branch → manual deploy to admin.tech.forworship.org
4. **API:** Same codebase, potentially separate deployment configuration

## Laravel Application Architecture

### Technology Stack
- **Backend:** Laravel 12 (PHP 8.3)
- **Database:** PostgreSQL
- **Authentication:** Laravel Sanctum + Fortify with TOTP 2FA
- **Frontend:** Inertia.js + Vue 3 + Bootstrap 5/TailwindCSS
- **Build Tool:** Vite
- **Deployment:** cPanel Git with `.cpanel.yml` automation

### Multi-Tenant Configuration
- **Model:** Single database with tenant_id isolation
- **Admin Interface:** Church administrators manage their content
- **API Endpoints:** Serve content to Roku/TV applications
- **Security:** Global Eloquent scopes, 2FA required for admins

## Deployment Configuration Files

### .cpanel.yml (Auto-deployment script)
- Located in repository root
- Handles composer install, npm build, Laravel optimization
- Runs migrations and sets permissions
- Caches routes and config for production

### Environment Configuration
- **Production:** `.env` file with PostgreSQL settings
- **Security:** HTTPS enforcement, secure cookies
- **Caching:** Database sessions, file cache, optimized for shared hosting

## Security Considerations

### Domain Security
- **SSL:** All subdomains use HTTPS
- **Document Root:** Always points to `/public/` folder (never repository root)
- **File Protection:** `.env`, `composer.json`, and source code not web-accessible

### Application Security
- **Tenant Isolation:** Global scopes prevent cross-tenant data access
- **2FA Required:** Admin users must enable TOTP authentication
- **API Security:** Scoped Bearer tokens with expiration
- **Input Validation:** FormRequest classes for all user inputs

## Future Expansion Plan

### Phase 2 Deployments
1. **API Separation:** Deploy API-only version to api.tech.forworship.org
2. **Multi-Platform:** Same Laravel backend serves Roku, Apple TV, Fire TV
3. **Documentation:** Auto-generated API docs at docs.tech.forworship.org
4. **Monitoring:** Application monitoring and analytics

### Scaling Considerations
- **Database:** Prepared for tenant-per-schema or tenant-per-database
- **CDN:** Ready for asset delivery optimization
- **Caching:** Redis integration for improved performance
- **Background Jobs:** Queue processing for video sync and analytics

## Support Information

### Repository Access
- **GitHub:** https://github.com/pbrentyoung1/church-media-platform
- **License:** MIT License under Brent Young
- **Primary Contact:** Project maintainer

### Infrastructure Management
- **Hosting:** hosting.com reseller account
- **DNS:** Managed through domain registrar
- **SSL:** Automatic SSL through hosting provider
- **Backups:** Regular database and file backups

---

**Last Updated:** September 11, 2025
**Documentation Status:** Active deployment configuration
**Next Review:** After Phase 1 MVP completion
