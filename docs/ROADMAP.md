# Project Roadmap

## Node.js Architecture (v2) - Active Development

### ✅ Phase 0: Architecture Transition (COMPLETED)
- Laravel analysis and documentation complete
- Node.js stack design and planning
- All structural work preserved for reference
- Migration strategy defined

### 🚧 Phase 1: Core Infrastructure (Current - Weeks 1-4)
- Project structure setup (Node.js + TypeScript)
- React frontend with Tailwind CSS + Shadcn/ui
- Basic authentication (JWT + 2FA)
- Database setup (Prisma + PostgreSQL)
- Docker development environment

### 📋 Phase 2: Video Management (Weeks 5-8)
- Vimeo integration (import videos)
- Resi integration (live streaming)
- Video CRUD operations
- Playlist management with drag-and-drop
- Basic analytics dashboard

### 📋 Phase 3: Roku Integration (Weeks 9-12)
- Device linking system
- Public API for Roku apps
- Enhanced analytics and tracking
- Performance optimization
- Production deployment

### 📋 Phase 4: Advanced Features (Weeks 13-16)
- User management and permissions
- Advanced analytics and reporting
- Custom branding per tenant
- Media server planning for direct uploads

### 📋 Future Phases (Post-MVP)
- **Phase 5**: Direct upload capability (requires media server)
- **Phase 6**: PCO Services + Calendar integration
- **Phase 7**: PCO Groups integration
- **Phase 8**: PCO Media ingest + SSO
- **Phase 9**: Advanced enterprise features

## Legacy Laravel Roadmap (v1 - Preserved for Reference)

### ✅ M1–M6 (MVP) - ANALYSIS COMPLETED
- Foundation → Hardening (authentication system complete)

### M7–M9 (Future Planning)
- M7: PCO Services + Calendar
- M8: PCO Groups
- M9: PCO Media ingest + SSO

### Personas vs Features (Preserved Analysis)
- **Sarah** (Church Admin): Onboarding wizard, user management
- **James** (Content Manager): Bulk video import, analytics export
- **Maria** (Church Member): Roku app experience, easy discovery
- **David** (Pastor): Search functionality, persistent sessions
- **Denise** (Platform Admin): Meta-admin features, cross-tenant oversight

## Performance Targets (v2)

| Component | Target | Laravel v1 | Node.js v2 Goal |
|-----------|--------|------------|-----------------|
| API Response | <100ms | ~300ms | **70% improvement** |
| Build Time | <30s | ~2min | **75% improvement** |
| Cold Start | <2s | ~10s | **80% improvement** |
| Bundle Size | <500KB | ~2MB | **75% reduction** |

## Migration Strategy

### Data Migration
1. **Export** existing PostgreSQL data from Laravel
2. **Transform** to new Prisma schema
3. **Import** into Node.js database
4. **Validate** data integrity and relationships

### Feature Parity Checklist
- [ ] Multi-tenant authentication (JWT + 2FA)
- [ ] Video management (Vimeo + Resi integration)
- [ ] Playlist management with ordering
- [ ] Analytics dashboard with real-time metrics
- [ ] Device linking for Roku apps
- [ ] User management with role-based access
- [ ] Custom tenant branding
- [ ] Public API for TV applications
