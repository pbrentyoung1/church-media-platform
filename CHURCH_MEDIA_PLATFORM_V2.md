# Church Media Platform v2 - Modern React 19 + Node.js Architecture

## Project Overview

**A multi-tenant SaaS platform that creates and manages custom Roku channels for churches and ministries.** The core product is a personalized Roku channel for each church, built from Roku example templates and customized with their branding, content, and live streams. Moving from Laravel/PHP to a modern React 19 + Node.js stack for better performance, developer experience, and long-term flexibility.

## Technology Stack Transformation

### New Modern Stack
- **Backend**: Node.js + TypeScript + Fastify + Prisma ORM
- **Database**: PostgreSQL + Redis (sessions, caching, queues)
- **Frontend**: React 19 + TypeScript + Vite
- **UI Framework**: Inspinia v4.5.0 (React 19 + Bootstrap 5.3+) + Tailwind CSS utilities
- **Drag & Drop**: @dnd-kit library (included in Inspinia) for hierarchical content management
- **Forms**: React Hook Form + Zod validation (included in Inspinia)
- **Auth**: JWT + Refresh Tokens + TOTP 2FA
- **API**: REST with OpenAPI/Swagger documentation
- **Testing**: Vitest + Playwright + React Testing Library
- **Roku Apps**: Dynamic SceneGraph generation per tenant
- **Media Processing**: Sample videos → HLS/DASH conversion (FFmpeg/Video.dev/Transloadit/Cloudflare Stream)
- **Live Streaming**: Resi integration (port existing logic)

### Benefits of New Stack
- **Full Type Safety**: TypeScript across entire stack
- **Better Performance**: Node.js/Fastify vs PHP/Laravel
- **Modern DX**: Hot reload, better tooling, faster builds
- **Easier Deployment**: Single runtime, containerizable
- **Scalability**: Better horizontal scaling capabilities
- **Long-term Flexibility**: Modern ecosystem, active development

## Development Approach: Frontend-First

### Phase 1: Frontend UI/UX with Inspinia v4.5.0 (Current Focus)
1. **Setup Inspinia v4.5.0 Foundation**
   - React 19 + TypeScript + Bootstrap 5.3+ project structure
   - Configure @dnd-kit for drag-and-drop content management
   - Setup React Hook Form + Zod validation
   - Integrate Tailwind CSS utilities for custom styling

2. **Create Core UI Components with Sample Data**
   - Church admin dashboard with mock analytics
   - Drag-and-drop video card components for hierarchical organization
   - Video library with filtering/search (sample video files)
   - Playlist builder with hierarchical content management
   - Video upload interface with metadata extraction
   - Settings and branding customization

3. **Validate User Experience**
   - Test drag-and-drop workflows on desktop and mobile
   - Refine content organization patterns
   - Optimize for non-technical church staff

### Phase 2: Roku Channel Templates (Upcoming)
1. **SceneGraph Template Development**
   - Build customizable Roku channel templates using examples
   - Design template structure for church branding and content injection
   - Create channel preview system to show final Roku appearance
   - Implement template to HLS/DASH stream integration

2. **Roku Channel Generation Pipeline**
   - Template customization based on church branding
   - Content feed generation from organized video hierarchy
   - Channel package creation and deployment workflow

### Phase 3: Backend Implementation (Future)
1. **Set up Node.js + TypeScript + Fastify**
2. **Implement Prisma schema and migrations based on frontend data models**
3. **Build API endpoints to match frontend requirements**
4. **Integrate video processing pipeline (FFmpeg/Video.dev/Transloadit/Cloudflare Stream)**
5. **Implement Roku channel generation and deployment system**

## Core Architecture Principles

### Multi-Tenancy (Preserved from v1)
- **Tenant Scoping**: All queries automatically scoped by tenant
- **UUID Primary Keys**: Enhanced security and scalability
- **Data Isolation**: Critical for church data privacy
- **Subdomain Routing**: `tenant.churchmedia.app` architecture

### Security Framework
- **JWT Authentication**: Access + refresh token pattern
- **TOTP 2FA**: Required for admin users, optional for content managers
- **API Scopes**: Fine-grained permissions (read:catalog, write:videos, etc.)
- **Rate Limiting**: Per-tenant and per-endpoint limits
- **Input Validation**: Zod schemas for all inputs
- **SQL Injection Prevention**: Prisma ORM parameterized queries

### Data Model (Enhanced from v1)
```typescript
// Core entities with TypeScript types
interface Tenant {
  id: string;
  name: string;
  subdomain: string;
  customDomain?: string;
  settings: TenantSettings;
  branding: BrandingConfig;
  subscription: SubscriptionInfo;
}

interface User {
  id: string;
  tenantId: string;
  email: string;
  name: string;
  role: UserRole;
  twoFactorEnabled: boolean;
  lastLoginAt?: Date;
}

interface Video {
  id: string;
  tenantId: string;
  title: string;
  description?: string;
  source: 'vimeo' | 'youtube' | 'resi' | 'upload';
  externalId?: string;
  duration?: number;
  status: 'draft' | 'published' | 'archived';
  metadata: VideoMetadata;
}
```

## API Design

### REST Endpoints
```typescript
// Authentication
POST   /api/auth/login
POST   /api/auth/refresh
POST   /api/auth/logout
POST   /api/auth/2fa/setup
POST   /api/auth/2fa/verify

// Tenant Management
GET    /api/tenant/profile
PUT    /api/tenant/profile
GET    /api/tenant/branding
PUT    /api/tenant/branding

// Video Management
GET    /api/videos              // List with filtering/pagination
POST   /api/videos              // Create new video
GET    /api/videos/:id          // Get single video
PUT    /api/videos/:id          // Update video
DELETE /api/videos/:id          // Delete video
POST   /api/videos/import/vimeo // Import from Vimeo
POST   /api/videos/import/resi  // Import from Resi

// Playlist Management
GET    /api/playlists
POST   /api/playlists
GET    /api/playlists/:id
PUT    /api/playlists/:id
DELETE /api/playlists/:id
PUT    /api/playlists/:id/videos // Update video order

// Analytics
GET    /api/analytics/dashboard // Overview stats
GET    /api/analytics/videos/:id // Video-specific metrics
POST   /api/events             // Track viewing events

// Device Linking (for Roku/TV apps)
POST   /api/device/link/start  // Generate device codes
POST   /api/device/link/verify // Verify and approve device
GET    /api/device/links       // List linked devices
DELETE /api/device/links/:id   // Unlink device

// Public API (for Roku apps)
GET    /api/v1/catalog         // Video catalog for tenant
GET    /api/v1/branding        // Branding config for apps
GET    /api/v1/live           // Live stream data
POST   /api/v1/events         // Analytics events from devices
```

## Database Schema (Prisma)

### Enhanced Schema Design
```prisma
model Tenant {
  id            String   @id @default(cuid())
  name          String
  subdomain     String   @unique
  customDomain  String?  @unique
  settings      Json     @default("{}")
  branding      Json     @default("{}")
  createdAt     DateTime @default(now())
  updatedAt     DateTime @updatedAt
  
  users         User[]
  videos        Video[]
  playlists     Playlist[]
  events        Event[]
  deviceLinks   DeviceLink[]
  
  @@map("tenants")
}

model User {
  id                String    @id @default(cuid())
  tenantId          String
  email             String
  name              String
  passwordHash      String
  role              UserRole  @default(EDITOR)
  twoFactorSecret   String?
  twoFactorEnabled  Boolean   @default(false)
  lastLoginAt       DateTime?
  createdAt         DateTime  @default(now())
  updatedAt         DateTime  @updatedAt
  
  tenant            Tenant    @relation(fields: [tenantId], references: [id], onDelete: Cascade)
  refreshTokens     RefreshToken[]
  deviceLinks       DeviceLink[]
  
  @@unique([tenantId, email])
  @@map("users")
}

model Video {
  id            String      @id @default(cuid())
  tenantId      String
  title         String
  description   String?
  source        VideoSource
  externalId    String?
  playbackUrl   String?
  thumbnailUrl  String?
  duration      Int?        // seconds
  status        VideoStatus @default(DRAFT)
  metadata      Json        @default("{}")
  syncedAt      DateTime?
  createdAt     DateTime    @default(now())
  updatedAt     DateTime    @updatedAt
  
  tenant        Tenant      @relation(fields: [tenantId], references: [id], onDelete: Cascade)
  playlistItems PlaylistItem[]
  events        Event[]
  
  @@unique([tenantId, source, externalId])
  @@map("videos")
}

model Playlist {
  id            String   @id @default(cuid())
  tenantId      String
  title         String
  slug          String?
  description   String?
  thumbnailUrl  String?
  sortOrder     Int      @default(0)
  status        VideoStatus @default(DRAFT)
  createdAt     DateTime @default(now())
  updatedAt     DateTime @updatedAt
  
  tenant        Tenant   @relation(fields: [tenantId], references: [id], onDelete: Cascade)
  items         PlaylistItem[]
  
  @@unique([tenantId, slug])
  @@map("playlists")
}

model PlaylistItem {
  id         String   @id @default(cuid())
  playlistId String
  videoId    String
  position   Int
  createdAt  DateTime @default(now())
  
  playlist   Playlist @relation(fields: [playlistId], references: [id], onDelete: Cascade)
  video      Video    @relation(fields: [videoId], references: [id], onDelete: Cascade)
  
  @@unique([playlistId, videoId])
  @@unique([playlistId, position])
  @@map("playlist_items")
}

enum UserRole {
  ADMIN
  EDITOR
  VIEWER
}

enum VideoSource {
  VIMEO
  YOUTUBE
  RESI
  UPLOAD
}

enum VideoStatus {
  DRAFT
  PUBLISHED
  ARCHIVED
}
```

## Frontend Architecture

### React + TypeScript Structure
```
frontend/
├── src/
│   ├── components/         # Reusable UI components
│   │   ├── ui/            # Shadcn/ui base components
│   │   ├── forms/         # Form components with validation
│   │   └── layout/        # Layout components
│   ├── pages/             # Page components
│   │   ├── Dashboard/
│   │   ├── Videos/
│   │   ├── Playlists/
│   │   └── Settings/
│   ├── hooks/             # Custom React hooks
│   ├── lib/               # Utilities and API client
│   ├── types/             # TypeScript type definitions
│   └── stores/            # State management (Zustand)
```

### Key React Features
- **React Query/TanStack Query**: Server state management
- **React Hook Form + Zod**: Form handling and validation
- **Zustand**: Client state management
- **React Router**: SPA routing
- **Framer Motion**: Animations and transitions

## Development Workflow

### Local Development Setup
```bash
# Backend
cd backend
npm install
npm run db:dev          # Start PostgreSQL + Redis
npm run db:migrate      # Run Prisma migrations
npm run dev             # Start Fastify server with hot reload

# Frontend  
cd frontend
npm install
npm run dev             # Start Vite dev server

# Full stack
npm run dev:all         # Start both backend and frontend
```

### Quality Assurance
- **TypeScript**: Strict type checking
- **ESLint + Prettier**: Code formatting and linting
- **Vitest**: Unit and integration tests
- **Playwright**: E2E testing
- **Husky**: Pre-commit hooks for quality gates

### Deployment Strategy
- **Docker**: Containerized deployment
- **Railway/Vercel**: Easy deployment options
- **CI/CD**: GitHub Actions for automated testing and deployment

## Migration Strategy from Laravel v1

### Data Migration
1. **Export existing PostgreSQL data**
2. **Transform to new schema** using migration scripts
3. **Import into Prisma-managed database**
4. **Validate data integrity**

### Feature Parity Checklist
- [ ] Multi-tenant authentication
- [ ] Video management (CRUD operations)
- [ ] Vimeo integration (import videos)
- [ ] Resi integration (live streams)
- [ ] Playlist management
- [ ] Analytics dashboard
- [ ] Device linking for TV apps
- [ ] User management and 2FA
- [ ] Tenant branding customization
- [ ] Public API for Roku apps

### Roku App Integration
- **No changes required** to existing Roku SceneGraph app
- **Same API endpoints** with improved performance
- **Enhanced analytics** with better event tracking

## Success Metrics

### Performance Improvements
- **API Response Time**: <100ms (vs ~300ms Laravel)
- **Build Time**: <30s (vs ~2min Laravel)
- **Cold Start**: <2s (vs ~10s Laravel)
- **Bundle Size**: <500KB initial load

### Developer Experience
- **Type Safety**: 100% TypeScript coverage
- **Hot Reload**: <1s change detection
- **Test Suite**: <10s unit test execution
- **Documentation**: Auto-generated API docs

### Business Metrics
- **Time to Market**: 50% faster feature development
- **Bug Reduction**: 70% fewer runtime errors
- **Onboarding**: New developers productive in 1 day vs 1 week

This modern architecture provides a solid foundation for scaling the Church Media Platform while significantly improving the development experience and long-term maintainability.