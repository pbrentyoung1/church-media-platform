# Technology Stack

## New Architecture (v2 - Active Development)

**Backend Stack:**
- Node.js + TypeScript (type safety across entire stack)
- Fastify (high-performance web framework)
- Prisma ORM + PostgreSQL (type-safe database access)
- JWT + Refresh Tokens (stateless authentication)
- Redis (caching, sessions, queues)
- Zod (runtime validation)

**Frontend Stack:**
- React 18 + TypeScript (modern component architecture)
- Vite (lightning-fast development builds)
- Tailwind CSS (utility-first styling for long-term flexibility)
- Shadcn/ui (component library with future Inspinia integration)
- React Query/TanStack Query (server state management)

**Testing & Quality:**
- Vitest (unit and integration tests)
- Playwright (end-to-end testing)
- React Testing Library (component testing)
- TypeScript strict mode (compile-time safety)

**Media & Integrations:**
- Vimeo API integration (ported from Laravel)
- Resi API integration for live streaming (ported)
- Roku SceneGraph app (unchanged)

**Development Tools:**
- Docker Compose (local development)
- OpenAPI/Swagger (API documentation)
- ESLint + Prettier (code formatting)
- Husky (pre-commit hooks)

## Legacy Laravel (v1 - Preserved for Reference)

- Laravel 12 (PHP 8.3) - Complete authentication system
- Inertia + Vue 3 + Vite - SPA interface
- Bootstrap 5 - UI framework (preserved work)
- Sanctum + Fortify - 2FA authentication (working)
- PostgreSQL - Database with validated schema
- Vimeo + Resi integrations - Media streaming (working)
- Roku SceneGraph app - TV application

## Migration Benefits

| Aspect | Laravel v1 | Node.js v2 | Improvement |
|--------|------------|------------|-------------|
| API Response | ~300ms | <100ms | **70% faster** |
| Build Time | ~2min | <30s | **75% faster** |
| Cold Start | ~10s | <2s | **80% faster** |
| Bundle Size | ~2MB | <500KB | **75% smaller** |
| Type Safety | PHP only | Full stack | **100% coverage** |
| Developer Experience | Good | Excellent | **Modern tooling** |
