# Church Media Platform v2 - Modern Node.js Architecture

## Project Overview

A complete rebuild of the multi-tenant SaaS platform for churches and ministries to manage branded video channels, integrate live streams, and publish to Roku/TV apps. Moving from Laravel/PHP to a modern Node.js stack for better performance, developer experience, and long-term flexibility.

## Technology Stack Transformation

### New Modern Stack
- **Backend**: Node.js + TypeScript + Fastify + Prisma ORM
- **Database**: PostgreSQL + Redis (sessions, caching, queues)
- **Frontend**: React 18 + TypeScript + Vite + Tailwind CSS
- **UI Components**: Shadcn/ui + Inspinia (Tailwind version when released)
- **Auth**: JWT + Refresh Tokens + TOTP 2FA
- **Validation**: Zod (runtime validation)
- **API**: REST with OpenAPI/Swagger documentation
- **Testing**: Vitest + Playwright + React Testing Library
- **Roku App**: Existing SceneGraph app (unchanged)
- **Media Integrations**: Vimeo + Resi (port existing logic)

### Benefits of New Stack
- **Full Type Safety**: TypeScript across entire stack
- **Better Performance**: Node.js/Fastify vs PHP/Laravel
- **Modern DX**: Hot reload, better tooling, faster builds
- **Easier Deployment**: Single runtime, containerizable
- **Scalability**: Better horizontal scaling capabilities
- **Long-term Flexibility**: Modern ecosystem, active development

## Development Approach: UI-First

### Phase 1: UI/UX Design & Prototyping (Weeks 1-2)
1. **Wireframe Key Workflows**
   - Tenant dashboard with analytics
   - Video management (upload, import from Vimeo/Resi)
   - Playlist creation and management
   - Live stream scheduling
   - Device linking for TV apps
   - User management and 2FA setup

2. **Create React Components with Mock Data**
   - Dashboard with sample metrics
   - Video library with filtering/search
   - Playlist builder with drag-and-drop
   - Settings and branding customization
   - Authentication flows

3. **Validate User Experience**
   - Test with actual church staff
   - Refine workflows based on feedback
   - Optimize for non-technical users

### Phase 2: API Design & Documentation (Week 3)
1. **Define Data Models** (based on UI requirements)
2. **Design REST API Contracts**
3. **Create OpenAPI/Swagger documentation**
4. **Plan authentication and authorization flows**

### Phase 3: Backend Implementation (Weeks 4-6)
1. **Set up Node.js + TypeScript + Fastify**
2. **Implement Prisma schema and migrations**
3. **Build API endpoints to match frontend needs**
4. **Integrate external services (Vimeo, Resi)**

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