# Church Media Platform Development Workflow

## Overview

This document outlines the development workflow, project structure, and best practices for the Church Media Platform v2, focusing on the React + Inspinia frontend and Node.js backend integration.

## Project Structure

```
forworship/
├── frontend/                   # React + TypeScript + Vite
│   ├── src/
│   │   ├── vendor/inspinia/    # Inspinia React components (unchanged)
│   │   │   ├── components/
│   │   │   │   ├── dashboard/  # Dashboard widgets, StatCards
│   │   │   │   ├── tables/     # DataTable, pagination, filters
│   │   │   │   ├── forms/      # Form components, validation
│   │   │   │   ├── upload/     # FileUploader, drag-drop
│   │   │   │   ├── auth/       # Login, registration, 2FA
│   │   │   │   ├── calendar/   # FullCalendar integration
│   │   │   │   ├── charts/     # ApexCharts, analytics
│   │   │   │   └── layout/     # Navigation, sidebar, layout
│   │   │   ├── hooks/          # React hooks for common functionality
│   │   │   ├── utils/          # Utility functions
│   │   │   └── types/          # TypeScript definitions
│   │   ├── components/         # Church-specific components
│   │   │   ├── church-media/
│   │   │   │   ├── VideoPlayer.tsx
│   │   │   │   ├── RokuManager.tsx
│   │   │   │   ├── LiveStreamControl.tsx
│   │   │   │   └── TenantBranding.tsx
│   │   │   └── ui/             # Shadcn/ui components (minimal)
│   │   ├── pages/              # Route components
│   │   │   ├── Dashboard.tsx
│   │   │   ├── VideoLibrary.tsx
│   │   │   ├── Events.tsx
│   │   │   ├── Playlists.tsx
│   │   │   ├── Analytics.tsx
│   │   │   └── RokuChannel.tsx
│   │   ├── lib/
│   │   │   ├── api/            # React Query API calls
│   │   │   │   ├── videos.ts
│   │   │   │   ├── events.ts
│   │   │   │   ├── playlists.ts
│   │   │   │   └── roku.ts
│   │   │   ├── tenant/         # Multi-tenant utilities
│   │   │   │   ├── branding.ts
│   │   │   │   └── permissions.ts
│   │   │   └── roku/           # Roku integration
│   │   │       ├── feed-generator.ts
│   │   │       └── channel-manager.ts
│   │   ├── types/              # TypeScript definitions
│   │   │   ├── church-media.ts
│   │   │   ├── roku.ts
│   │   │   └── inspinia-extensions.ts
│   │   ├── styles/             # CSS and styling
│   │   │   ├── globals.css     # Bootstrap + custom CSS
│   │   │   └── tenant-themes.css
│   │   └── utils/              # Shared utilities
│   ├── public/                 # Static assets
│   ├── package.json
│   ├── vite.config.ts
│   └── tsconfig.json
├── backend/                    # Node.js + TypeScript + Fastify
│   ├── src/
│   │   ├── routes/             # API route handlers
│   │   ├── services/           # Business logic
│   │   ├── models/             # Prisma models
│   │   ├── middleware/         # Authentication, validation
│   │   └── utils/              # Shared utilities
│   ├── prisma/                 # Database schema and migrations
│   ├── package.json
│   └── tsconfig.json
├── docs/                       # Documentation
│   ├── FRONTEND_ARCHITECTURE.md
│   ├── FRONTEND_DATA_MODELS.md
│   ├── INSPINIA_COMPONENT_MAPPING.md
│   ├── ROKU_INTEGRATION_SPECS.md
│   └── DEVELOPMENT_WORKFLOW.md
├── CLAUDE.md                   # Claude AI instructions
└── README.md                   # Project overview
```

## Development Commands

### Frontend Development
```bash
# Navigate to frontend directory
cd frontend

# Install dependencies
npm install

# Start development server with hot reload
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Type checking
npm run type-check

# Linting
npm run lint
npm run lint:fix

# Testing
npm run test
npm run test:watch
npm run test:coverage
```

### Backend Development
```bash
# Navigate to backend directory
cd backend

# Install dependencies
npm install

# Start development server
npm run dev

# Build for production
npm run build

# Start production server
npm start

# Database operations
npx prisma generate
npx prisma db push
npx prisma migrate dev
npx prisma studio

# Testing
npm run test
npm run test:watch
```

## Development Workflow

### 1. Feature Development Process

#### Phase 1: Planning and Setup
1. **Identify Inspinia components** that can be utilized for the feature
2. **Review data models** in `docs/FRONTEND_DATA_MODELS.md`
3. **Check component mapping** in `docs/INSPINIA_COMPONENT_MAPPING.md`
4. **Create feature branch** from `main`

#### Phase 2: Frontend Implementation
1. **Start with Inspinia examples** for similar functionality
2. **Create page component** in `src/pages/`
3. **Utilize Inspinia components** with minimal modifications
4. **Replace mock data** with church-specific data structures
5. **Add church-specific business logic** on top of Inspinia foundation

#### Phase 3: API Integration
1. **Define API calls** in `src/lib/api/`
2. **Set up React Query** hooks for data fetching
3. **Connect components** to real data
4. **Handle loading and error states**

#### Phase 4: Testing and Quality
1. **Unit tests** for custom components
2. **Integration tests** for API connections
3. **Type checking** with TypeScript
4. **Visual testing** with Storybook (if needed)

### 2. Component Development Strategy

#### Direct Utilization (No Changes)
```typescript
// Use Inspinia components as-is
import { StatCard } from '@/vendor/inspinia/components/dashboard'
import { DataTable } from '@/vendor/inspinia/components/tables'
import { FileUploader } from '@/vendor/inspinia/components/upload'

// Example: Video metrics dashboard
const VideoMetrics = () => {
  return (
    <div className="row">
      <StatCard
        title="Total Videos"
        value={videoCount}
        icon={VideoIcon}
        variant="primary"
      />
    </div>
  )
}
```

#### Minimal Adaptation (5-10% Changes)
```typescript
// Extend Inspinia types for church-specific data
import { DataTableProps } from '@/vendor/inspinia/types'

interface VideoTableProps extends DataTableProps {
  videos: Video[]
  onRokuToggle: (videoId: string) => void
}

// Customize columns for church content
const videoColumns = [
  ...inspiniaDefaultColumns,
  {
    key: 'roku_enabled',
    header: 'Roku Channel',
    render: (video: Video) => (
      <RokuToggle 
        enabled={video.roku_settings.enabled}
        onChange={() => onRokuToggle(video.id)}
      />
    )
  }
]
```

#### Custom Development (10-15%)
```typescript
// New components built on Inspinia foundation
import { Modal } from '@/vendor/inspinia/components/layout'
import { Button } from '@/vendor/inspinia/components/forms'

const RokuChannelManager = () => {
  return (
    <Modal title="Roku Channel Management">
      {/* Custom roku management logic */}
      <RokuContentFeed />
      <RokuAnalytics />
      
      {/* Reuse Inspinia buttons */}
      <Button variant="primary" onClick={publishChannel}>
        Publish Channel
      </Button>
    </Modal>
  )
}
```

### 3. Multi-Tenant Development

#### Theme Customization
```css
/* Dynamic CSS custom properties */
:root {
  --bs-primary: var(--tenant-primary-color);
  --bs-secondary: var(--tenant-secondary-color);
  --bs-font-family-base: var(--tenant-font-family);
}

/* Tenant-specific overrides */
[data-tenant="first-church"] {
  --tenant-primary-color: #1a365d;
  --tenant-secondary-color: #2c5282;
}
```

#### Component Branding
```typescript
// Tenant context provider
const TenantProvider = ({ children }) => {
  const tenant = useTenant()
  
  useEffect(() => {
    document.documentElement.style.setProperty(
      '--tenant-primary-color',
      tenant.branding.primary_color
    )
  }, [tenant])
  
  return (
    <TenantContext.Provider value={tenant}>
      {children}
    </TenantContext.Provider>
  )
}
```

### 4. Data Integration Patterns

#### React Query Setup
```typescript
// API client configuration
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000, // 5 minutes
      cacheTime: 10 * 60 * 1000, // 10 minutes
    },
  },
})

// Custom hooks for church data
export const useVideos = (filters?: VideoFilters) => {
  return useQuery({
    queryKey: ['videos', filters],
    queryFn: () => apiClient.videos.list(filters),
  })
}

export const useVideoUpload = () => {
  return useMutation({
    mutationFn: apiClient.videos.upload,
    onSuccess: () => {
      queryClient.invalidateQueries(['videos'])
    },
  })
}
```

#### Form Integration
```typescript
// Combine Inspinia forms with church validation
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { VideoUploadSchema } from '@/types/church-media'
import { FormInput } from '@/vendor/inspinia/components/forms'

const VideoUploadForm = () => {
  const { control, handleSubmit } = useForm<VideoUploadForm>({
    resolver: zodResolver(VideoUploadSchema)
  })
  
  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <FormInput
        name="title"
        label="Video Title"
        control={control}
        required
      />
      {/* Church-specific fields */}
      <FormInput
        name="speaker"
        label="Speaker/Pastor"
        control={control}
      />
      <FormInput
        name="scripture_reference"
        label="Scripture Reference"
        control={control}
      />
    </form>
  )
}
```

## Testing Strategy

### Unit Testing with Vitest
```typescript
// Component tests
import { render, screen } from '@testing-library/react'
import { VideoCard } from '@/components/church-media/VideoCard'

describe('VideoCard', () => {
  it('displays video information correctly', () => {
    const mockVideo = createMockVideo()
    render(<VideoCard video={mockVideo} />)
    
    expect(screen.getByText(mockVideo.title)).toBeInTheDocument()
    expect(screen.getByText(mockVideo.metadata.speaker)).toBeInTheDocument()
  })
})
```

### Integration Testing
```typescript
// API integration tests
import { renderHook, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { useVideos } from '@/lib/api/videos'

describe('useVideos hook', () => {
  it('fetches videos successfully', async () => {
    const queryClient = new QueryClient({
      defaultOptions: { queries: { retry: false } }
    })
    
    const { result } = renderHook(() => useVideos(), {
      wrapper: ({ children }) => (
        <QueryClientProvider client={queryClient}>
          {children}
        </QueryClientProvider>
      )
    })
    
    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true)
    })
  })
})
```

## Performance Optimization

### Code Splitting
```typescript
// Lazy load heavy components
const VideoPlayer = lazy(() => import('@/components/church-media/VideoPlayer'))
const RokuManager = lazy(() => import('@/components/church-media/RokuManager'))
const Analytics = lazy(() => import('@/pages/Analytics'))

// Route-based code splitting
const router = createBrowserRouter([
  {
    path: '/videos',
    element: <Suspense fallback={<LoadingSpinner />}>
      <VideoLibrary />
    </Suspense>
  }
])
```

### Bundle Optimization
```typescript
// Vite configuration for optimal bundling
export default defineConfig({
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          'vendor-inspinia': ['@/vendor/inspinia'],
          'vendor-ui': ['@tanstack/react-query', 'react-hook-form'],
          'church-media': ['@/components/church-media']
        }
      }
    }
  }
})
```

## Quality Assurance

### Code Quality Tools
- **TypeScript**: Strict type checking across all components
- **ESLint**: Custom rules for React and TypeScript
- **Prettier**: Consistent code formatting
- **Husky**: Pre-commit hooks for quality gates

### Quality Checklist
- [ ] TypeScript compilation passes without errors
- [ ] All components have proper prop types
- [ ] API calls include error handling
- [ ] Multi-tenant data scoping implemented
- [ ] Responsive design tested on mobile/desktop
- [ ] Accessibility requirements met
- [ ] Performance budgets respected

## Deployment Workflow

### Development Environment
1. **Frontend**: Vite dev server on `http://localhost:5173`
2. **Backend**: Node.js server on `http://localhost:3000`
3. **Database**: Local PostgreSQL with sample data
4. **Hot Reload**: Automatic reloading for both frontend and backend

### Production Build
1. **Frontend Build**: `npm run build` → `/dist` folder
2. **Backend Build**: `npm run build` → `/dist` folder
3. **Static Assets**: Served via CDN or nginx
4. **API Deployment**: Node.js server with PM2 process manager

This workflow ensures efficient development while maintaining code quality and leveraging Inspinia's proven component library for rapid church media platform development.