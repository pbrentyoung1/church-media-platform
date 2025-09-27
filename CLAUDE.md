# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**A multi-tenant SaaS platform that creates and manages custom Roku channels for churches and ministries.** The core product is a personalized Roku channel for each church, built from Roku example templates and customized with their branding, content, and live streams. **MIGRATING FROM LARAVEL TO MODERN NODE.JS STACK** for better performance, developer experience, and long-term flexibility.

### Core Platform Goal
- **Primary Output**: Custom Roku channels per church tenant
- **Channel Management**: Create, deploy, and update Roku SceneGraph apps
- **Content Integration**: Video catalogs, live streams, and church branding
- **Future Expansion**: Additional streaming platforms (Apple TV, Amazon Fire TV, etc.)

## Technology Stack

### Current (Laravel - DEPRECATED)
- **Status**: ⚠️ **LEGACY - DO NOT EXTEND** ⚠️
- **Backend**: Laravel 12 (PHP 8.2|8.4) 
- **Frontend**: Inertia + Vue 3 + Vite
- **Note**: Completed authentication fixes and model relationships - preserved for reference

### New Architecture (v2 - ACTIVE DEVELOPMENT)
- **Backend**: Node.js + TypeScript + Fastify + Prisma ORM
- **Frontend**: React 18 + TypeScript + Vite + Tailwind CSS
- **UI Components**: Shadcn/ui + Inspinia (Tailwind version)
- **Database**: PostgreSQL + Redis (sessions, caching, queues)
- **Auth**: JWT + Refresh Tokens + TOTP 2FA
- **Validation**: Zod (runtime validation)
- **API**: REST with OpenAPI/Swagger documentation
- **Testing**: Vitest + Playwright + React Testing Library
- **Media**: Vimeo API + Resi integrations (ported from Laravel)
- **Drag & Drop**: @dnd-kit library for hierarchical content management
- **Roku**: Dynamic SceneGraph app generation and management per tenant

## Development Commands

### Main Development Workflow (v2 - Node.js)
- Root directory is now the v2 implementation
- `docker compose up -d` - Start PostgreSQL + Redis services
- `npm run dev:all` - Start backend + frontend concurrently
- `npm run test` - Run full test suite
- `npm run build` - Build for production deployment

### Backend Commands (Node.js + Fastify)
- `cd backend && npm run dev` - Start API server with hot reload
- `npm run db:migrate` - Run Prisma database migrations
- `npm run db:studio` - Open Prisma Studio (database browser)
- `npm run db:seed` - Seed database with test data

### Frontend Commands (React + Vite)
- `cd frontend && npm run dev` - Start React development server
- `npm run build` - Build optimized production bundle
- `npm run preview` - Preview production build locally
- `npm run test:e2e` - Run Playwright end-to-end tests

### Legacy Reference (Laravel - READ ONLY)
- `cd church-media-platform` - Laravel app for business logic reference
- `composer test` - Run existing test suite (for understanding)
- **Note**: Do not extend or deploy Laravel code

## Architecture & Security

### Multi-Tenancy
- **Tenant Scoping**: Global TenantScope applied to all queries - all tenant-owned tables include `tenant_id`
- **UUID Primary Keys**: All tables use UUIDs as primary keys
- **Data Isolation**: Critical for security between church tenants

### Authentication & Authorization
- **2FA Required**: TOTP 2FA mandatory for admin users
- **Device Linking**: Requires fresh 2FA for device authentication
- **API Tokens**: Sanctum Bearer tokens with scopes (catalog:read, events:write, branding:read, live:read)
- **Token Expiration**: All tokens are scoped and expiring

### Core Data Model
- `tenants`, `users`, `videos`, `playlists`, `playlist_items`, `events`
- `roku_channels`, `roku_deployments`, `channel_templates`
- `video_metrics_daily`, `device_links`, `personal_access_tokens`

## API Endpoints
- `GET /api/v1/catalog` - Video catalog
- `GET /api/v1/branding` - Church branding
- `GET /api/v1/search?q=` - Search functionality
- `POST /api/v1/events` - Event creation
- `GET /api/v1/live` - Live stream data
- `POST /api/v1/roku/deploy` - Deploy Roku channel
- `GET /api/v1/roku/status` - Roku channel status

## Development Environment

### Local Setup
- Laravel Valet for local development
- DBngin (PostgreSQL)
- Mailpit for email testing
- Redis

### Project Structure
- **Main Laravel App**: `/church-media-platform/` - Core application (LEGACY)
- **Documentation**: Root `/docs/` - Comprehensive project documentation
- **Deployment**: `.cpanel.yml` for hosting.com automated deployments
- **Environment Files**: `.env`, `.env.example`, `.env.production`

### Current Repository Structure
```
forworship/                              # Project workspace & v2 implementation
├── backend/                            # Node.js + TypeScript + Fastify + Prisma
├── frontend/                           # React + TypeScript + Tailwind CSS
├── church-media-platform/              # Laravel reference (business logic only)
├── docs/                               # Architecture documentation
├── docker-compose.yml                  # Local development services (PostgreSQL + Redis)
├── package.json                        # Workspace configuration
├── CLAUDE.md                           # This file  
├── README.md                           # Main project overview
├── README_V2.md                        # Node.js architecture overview
└── CHURCH_MEDIA_PLATFORM_V2.md         # Complete v2 specification
```

### Deployment Structure
- **Main Site**: `forworship.org` → `/public_html/`
- **Platform**: `media.forworship.org` → `/media/frontend/dist/`
- **API**: Node.js backend runs on port 3001, proxied from platform
- **Development**: Local development in project root (`/forworship/`)
- **Production**: Built and deployed to server `/media/` directory

### Required PHP Extensions (Production)
- Core: bcmath, ctype, curl, dom, fileinfo, json, mbstring, openssl, pdo, tokenizer, xml, zip
- Database: pdo_pgsql (PostgreSQL)  
- Media: gd, imagick (image processing)
- Performance: opcache, redis (optional but recommended)

## Branching Strategy
- Feature branches from `staging`
- PR → `staging` (auto-deploys to staging)
- Merge `staging` → `main` + tag → production

## Security Checklist for PRs
- [ ] Tenant scope enforced on all queries
- [ ] Proper validation (FormRequest/Policy)
- [ ] Token scopes correctly configured
- [ ] No secrets in logs
- [ ] Rate limits applied where needed
- [ ] HMAC verification for webhooks

## Documentation Requirements
When making changes that affect:
- APIs: Update `/docs/API.md`
- Security: Update `/docs/SECURITY.md`
- Testing: Update `/docs/TESTING.md`
- Architecture: Add/update ADR in `/docs/ADR/`

## Testing Strategy

### Smoke Tests (Staging)
- Admin login + 2FA
- Playlist CRUD operations
- Vimeo import functionality
- Resi live stream visibility
- Roku channel generation and deployment
- Roku app performance (splash <2s, play <3s)

### Production Health Checks
- Login functionality
- Video playback
- API health endpoints

## Load Testing Parameters
- 100 tenants, 10k videos
- Tenant isolation penetration testing

## Subdomain Strategy (v2 - Redesigned)

### Current Subdomain Architecture
```
# Multi-level subdomain strategy for better organization
{tenant}.media.forworship.org     # Primary tenant access
{tenant}.api.media.forworship.org # API endpoint (optional)
admin.media.forworship.org        # Platform administration
api.media.forworship.org          # Shared API services
docs.media.forworship.org         # API documentation
status.media.forworship.org       # System status monitoring
```

### Benefits of New Structure
- **Scalability**: Easier CDN and load balancer configuration
- **Security**: Separate API domains for better isolation
- **Performance**: Dedicated subdomains for different services
- **Flexibility**: Support for custom domains per tenant

### Custom Domain Support
```
media.firstchurch.org → {tenant-id}.media.forworship.org
videos.mycoolchurch.com → {tenant-id}.media.forworship.org

# ForWorship ecosystem
forworship.org                    # Main ForWorship website
media.forworship.org              # Church Media Platform (this app)
plan.forworship.org               # Future planning tool
create.forworship.org             # Future creation tool
```

---

# Development Instructions
**CURRENT STATUS**: Fresh start approach - clean v2 implementation
**ACTIVE DEVELOPMENT**: Node.js v2 architecture implementation (no migration needed)
**LEGACY LARAVEL**: Reference only for business logic understanding
**UI-FIRST APPROACH**: Build React components with mock data first
**TYPE SAFETY**: Maintain TypeScript across entire stack
**NO USERS**: Clean slate - no backward compatibility requirements

## Migration Status

### ✅ Completed Laravel Analysis
- Authentication system fixes (UUID sessions)
- Model relationships and factories
- Form request validations
- Database schema documentation

### 🚧 Current Phase: Architecture Transition
- [x] Requirements analysis and new architecture design
- [x] Documentation updates and comprehensive planning
- [x] Technology stack selection and validation
- [ ] v2 project structure setup
- [ ] React frontend with mock data
- [ ] Node.js backend implementation

### 📋 Preserved Work
All Laravel structural analysis preserved in:
- `/docs/SESSION_WORK_SUMMARY.md`
- `/docs/MODEL_RELATIONSHIPS.md`
- `/docs/TECHNICAL_FIXES.md`
- `/docs/DATABASE_SCHEMA.md`

**Next Steps**: Begin development in `/media-platform-v2/` directory

## Getting Started with v2 Development

### 1. Initial Setup
```bash
# Root directory is now the v2 implementation
npm run install:all          # Install all dependencies
docker compose up -d          # Start PostgreSQL + Redis + Adminer
```

### 2. Development Workflow
```bash
# Start full development environment
npm run dev:all              # Backend + Frontend concurrently

# Or start individually
cd backend && npm run dev     # API server on :3001
cd frontend && npm run dev    # React app on :3000
```

### 3. Key Development Files
- **Backend**: `/backend/src/`
- **Frontend**: `/frontend/src/`
- **Database**: Prisma schema in `/backend/prisma/`
- **Documentation**: API docs auto-generated at `http://localhost:3001/docs`

## Key Documentation Files

### Architecture & Planning
- **[README_V2.md](README_V2.md)** - Modern Node.js architecture overview
- **[CHURCH_MEDIA_PLATFORM_V2.md](CHURCH_MEDIA_PLATFORM_V2.md)** - Complete v2 technical specification
- **[docs/MIGRATION_SUMMARY.md](docs/MIGRATION_SUMMARY.md)** - Laravel to Node.js migration plan
- **[docs/SUBDOMAIN_STRATEGY.md](docs/SUBDOMAIN_STRATEGY.md)** - Enhanced multi-level subdomain architecture

### Laravel Documentation (v1 - Preserved)
- **[docs/API.md](docs/API.md)** - Complete API documentation
- **[docs/SECURITY.md](docs/SECURITY.md)** - Security implementation details
- **[docs/TESTING.md](docs/TESTING.md)** - Test suite documentation

## Development Priority & Strategy

### **Frontend-First Development Approach**
**CLEAN START APPROACH**: No users exist - fresh v2 implementation without migration concerns:

#### Phase 1: Frontend UI/UX (Current Focus)
1. **Frontend Foundation**: React + TypeScript + Tailwind CSS setup
2. **Drag & Drop System**: @dnd-kit implementation for hierarchical content management
3. **Responsive Video Cards**: Mobile and desktop-friendly drag handles
4. **Church Admin Dashboard**: Complete content management interface with mock data
5. **Vimeo Integration UI**: Smart metadata extraction and auto-population interface

#### Phase 2: Roku Channel Templates
6. **SceneGraph Templates**: Build customizable Roku channel templates
7. **Channel Preview**: Show how final Roku channel will appear
8. **Template Customization**: Branding and content injection system

#### Phase 3: Backend Implementation
9. **API Development**: Node.js + Fastify backend to support frontend requirements
10. **Database Schema**: PostgreSQL implementation based on frontend data models
11. **Roku Generation**: Backend systems to create and deploy channels

### **Key UI/UX Requirements**
- **Cross-Platform Drag & Drop**: Desktop mouse + mobile touch support
- **Hierarchical Content Management**: Categories, playlists, and video organization
- **Smart Content Ingestion**: Auto-populate from Vimeo API metadata
- **Responsive Design**: Seamless mobile and desktop experience
- **Real-time Preview**: Visual feedback for Roku channel appearance

### **Competitive Analysis & Inspiration**
- **Zype.com**: API-first architecture, multi-platform distribution, faith industry focus
- **inoRain.com**: White-label OTT platform, Electronic Program Guide, custom branding
- **Key Learnings**: Dashboard-centric design, visual content organization, real-time preview