# Church Media Platform Frontend Architecture

## Overview

This document outlines the frontend architecture for the Church Media Platform v2, leveraging Inspinia React Bootstrap admin template for rapid development while building church-specific media management capabilities.

## Architecture Decision

**Primary Strategy**: Maximum utilization of Inspinia's existing components (85-90% code reuse) with church media customizations built on top.

**Technology Stack**:
- **UI Framework**: Inspinia React + Bootstrap 5.3.7
- **Core**: React 19.1 + TypeScript + Vite
- **Forms**: React Hook Form 7.58 + Zod validation
- **Tables**: @tanstack/react-table 8.21
- **File Upload**: React-dropzone with Inspinia FileUploader
- **Calendar**: FullCalendar integration
- **Charts**: ApexCharts + ECharts for analytics
- **State Management**: React Query + React Context

## Key Benefits

### Development Speed: 85% Faster
- **Dashboard**: 95% complete (data mapping only)
- **Data Tables**: 100% complete (column configuration only)
- **Forms**: 90% complete (field customization only)
- **File Upload**: 100% complete (validation tweaks only)
- **Authentication**: 95% complete (API integration only)

### Professional Quality
- Enterprise-grade admin interface
- Proven UX patterns and accessibility
- Responsive mobile-first design
- Consistent component library

## Implementation Phases

### Phase 1: Foundation Setup (Week 1)
- Integrate Inspinia dependencies
- Configure TypeScript paths and imports
- Set up church media data models
- Configure Vite build system

### Phase 2: Component Mapping (Week 2)
- Dashboard with church metrics
- Video library management
- Navigation and layout customization
- Authentication system integration

### Phase 3: Church Features (Week 3)
- Event management and live streaming
- Roku channel management interface
- Multi-tenant branding system
- Content workflow management

### Phase 4: API Integration (Week 4)
- Node.js backend connection
- React Query data layer
- Authentication and authorization
- Real-time updates

### Phase 5: Advanced Features (Week 5)
- Analytics and reporting
- Video production pipeline
- Roku channel deployment
- Performance optimization

## File Structure

```
frontend/src/
├── vendor/inspinia/           # Inspinia components (unchanged)
├── components/
│   ├── church-media/          # Church-specific components
│   │   ├── VideoPlayer.tsx
│   │   ├── RokuManager.tsx
│   │   └── LiveStreamControl.tsx
│   └── ui/                    # Shadcn/ui (minimal usage)
├── pages/
│   ├── Dashboard.tsx          # Inspinia dashboard + church data
│   ├── VideoLibrary.tsx       # Inspinia DataTable + video data
│   ├── Events.tsx             # Inspinia calendar + church events
│   └── RokuChannel.tsx        # Roku management interface
├── lib/
│   ├── api/                   # React Query API calls
│   ├── tenant/                # Multi-tenant utilities
│   └── roku/                  # Roku integration utilities
└── types/
    ├── church-media.ts        # Church platform types
    ├── roku.ts                # Roku-specific types
    └── inspinia-extensions.ts # Extended Inspinia types
```

## Component Mapping Strategy

### Direct Utilization (No Changes)
- **StatCards**: Perfect for video/playlist/event metrics
- **DataTable**: Complete solution for content management
- **FileUploader**: Ready for video/thumbnail uploads
- **Calendar**: Event scheduling and live stream management
- **Navigation**: Hierarchical menu with permissions
- **Layout System**: Multi-tenant theme customization

### Adaptation Required (Minimal Changes)
- **Dashboard**: Replace mock data with church metrics
- **Menu Structure**: Church media navigation items
- **Form Components**: Church-specific validation rules
- **Authentication**: Connect to Node.js JWT system

### Custom Development (New Components)
- **VideoPlayer**: Media playback integration
- **RokuManager**: Channel deployment interface
- **LiveStreamControl**: Stream monitoring and control
- **TenantBranding**: Dynamic theme switching

## Data Flow Architecture

1. **Authentication**: Inspinia components → JWT tokens → Node.js verification
2. **Content Management**: Inspinia forms/tables → Node.js API → PostgreSQL
3. **File Upload**: Inspinia uploader → Node.js storage → CDN/local storage
4. **Analytics**: Multiple sources → Inspinia charts → Dashboard display
5. **Roku Integration**: CMS content → JSON feeds → Roku channel

## Multi-Tenant Implementation

### Theme Customization
```css
:root {
  --bs-primary: var(--tenant-primary-color);
  --bs-secondary: var(--tenant-secondary-color);
  --bs-font-family-base: var(--tenant-font-family);
}
```

### Layout Personalization
- Dynamic logo replacement in navigation
- Tenant-specific menu items and permissions
- Customizable dashboard layouts
- Branded login screens

## Performance Considerations

- **Code Splitting**: Lazy load Inspinia components
- **Bundle Optimization**: Import only needed components
- **Caching Strategy**: React Query for API data
- **Image Optimization**: Video thumbnails and media assets
- **CDN Integration**: Static asset delivery

## Development Workflow

1. **Start with Inspinia examples** for each new feature
2. **Replace mock data** with church media data structures
3. **Customize styling** for church branding needs
4. **Connect APIs** using React Query patterns
5. **Add business logic** on top of Inspinia foundation

## Quality Assurance

### Testing Strategy
- **Unit Tests**: Vitest for component logic
- **Integration Tests**: API connectivity and data flow
- **E2E Tests**: Playwright for user workflows
- **Visual Testing**: Storybook for component documentation

### Code Quality
- **TypeScript**: Strict type checking throughout
- **ESLint**: Code quality and consistency rules
- **Prettier**: Automatic code formatting
- **Husky**: Pre-commit hooks for quality gates

## Deployment Strategy

### Development Environment
- **Local**: Vite dev server with hot reload
- **API Integration**: Connect to Node.js backend
- **Database**: PostgreSQL with sample church data
- **File Storage**: Local development storage

### Production Environment
- **Build**: Optimized production bundle
- **CDN**: Static asset delivery
- **API**: Production Node.js backend
- **Database**: Production PostgreSQL cluster
- **Monitoring**: Error tracking and performance metrics

This architecture provides a solid foundation for rapid development while maintaining professional quality and scalability for multi-tenant church media management.