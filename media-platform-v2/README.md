# Church Media Platform v2

Modern Node.js + React implementation of the multi-tenant church media platform.

## Quick Start

```bash
# Start development environment
docker compose up -d          # PostgreSQL + Redis
npm run dev:all               # Backend + Frontend

# Access applications
http://localhost:3000         # React frontend
http://localhost:3001         # Node.js API
http://localhost:3001/docs    # API documentation
```

## Project Structure

```
media-platform-v2/
├── backend/                  # Node.js + TypeScript + Fastify
├── frontend/                 # React + TypeScript + Tailwind
├── docker-compose.yml        # Local development services
└── README.md                 # This file
```

## Development Workflow

1. **Backend**: `cd backend && npm run dev`
2. **Frontend**: `cd frontend && npm run dev`
3. **Full Stack**: `npm run dev:all` (from root)

## Architecture

- **Multi-tenant SaaS** with subdomain routing
- **JWT Authentication** with TOTP 2FA
- **PostgreSQL** with Prisma ORM
- **Redis** for sessions and caching
- **TypeScript** for full type safety

## Deployment

Builds deploy to server `/media/` directory:
- Frontend: `/media/frontend/dist/`
- Backend: Node.js service on port 3001

See parent directory documentation for complete architecture details.