# Church Media Platform Data Models

## Overview

This document defines the TypeScript interfaces and data structures for the Church Media Platform frontend, designed to work seamlessly with both Inspinia components and our Node.js backend API.

## Core Data Models

### Tenant & Authentication

```typescript
// Tenant information for multi-tenant architecture
interface Tenant {
  id: string
  name: string
  slug: string
  branding: {
    primary_color: string
    secondary_color: string
    logo_url?: string
    favicon_url?: string
    font_family?: string
  }
  settings: {
    timezone: string
    default_language: 'en' | 'es' | 'fr'
    features: {
      live_streaming: boolean
      roku_channel: boolean
      analytics: boolean
      multi_campus: boolean
    }
  }
  subscription_tier: 'basic' | 'pro' | 'enterprise'
  created_at: Date
  updated_at: Date
}

// User authentication and roles
interface User {
  id: string
  tenant_id: string
  email: string
  name: string
  avatar_url?: string
  role: 'admin' | 'editor' | 'viewer'
  permissions: string[]
  two_factor_enabled: boolean
  last_login_at?: Date
  created_at: Date
  updated_at: Date
}

// JWT authentication response
interface AuthResponse {
  access_token: string
  refresh_token: string
  user: User
  tenant: Tenant
  expires_in: number
}
```

### Content Management

```typescript
// Video content model
interface Video {
  id: string
  tenant_id: string
  title: string
  description?: string
  duration: number // in seconds
  file_url: string
  thumbnail_url?: string
  preview_url?: string
  file_size: number // in bytes
  mime_type: string
  resolution: '720p' | '1080p' | '4K' | 'unknown'
  status: 'uploading' | 'processing' | 'ready' | 'error'
  upload_progress?: number
  metadata: {
    speaker?: string
    series?: string
    scripture_reference?: string
    tags: string[]
    language: string
    recorded_at?: Date
  }
  analytics: {
    view_count: number
    engagement_score: number
    last_viewed_at?: Date
  }
  roku_settings: {
    enabled: boolean
    category: string
    featured: boolean
  }
  created_by: string
  created_at: Date
  updated_at: Date
}

// Playlist/collection model
interface Playlist {
  id: string
  tenant_id: string
  name: string
  description?: string
  thumbnail_url?: string
  type: 'sermon_series' | 'event_collection' | 'custom'
  videos: PlaylistVideo[]
  settings: {
    auto_play: boolean
    shuffle_enabled: boolean
    loop_enabled: boolean
  }
  roku_settings: {
    enabled: boolean
    featured: boolean
    sort_order: number
  }
  created_by: string
  created_at: Date
  updated_at: Date
}

// Playlist video relationship
interface PlaylistVideo {
  video_id: string
  sort_order: number
  added_at: Date
  video?: Video // Populated when needed
}
```

### Events & Live Streaming

```typescript
// Church event model
interface ChurchEvent {
  id: string
  tenant_id: string
  title: string
  description?: string
  type: 'service' | 'special_event' | 'conference' | 'meeting'
  start_time: Date
  end_time: Date
  timezone: string
  location?: {
    name: string
    address?: string
    room?: string
  }
  recurrence?: {
    type: 'weekly' | 'monthly' | 'yearly'
    interval: number
    end_date?: Date
    days_of_week?: number[] // 0=Sunday, 1=Monday, etc.
  }
  live_stream?: {
    enabled: boolean
    stream_url?: string
    stream_key?: string
    status: 'scheduled' | 'live' | 'ended'
    viewer_count?: number
    chat_enabled: boolean
  }
  registration?: {
    required: boolean
    max_attendees?: number
    current_count: number
    registration_url?: string
  }
  created_by: string
  created_at: Date
  updated_at: Date
}

// Live streaming session
interface LiveStream {
  id: string
  tenant_id: string
  event_id?: string
  title: string
  description?: string
  stream_url: string
  stream_key: string
  status: 'scheduled' | 'starting' | 'live' | 'ended' | 'error'
  scheduled_start: Date
  actual_start?: Date
  actual_end?: Date
  settings: {
    chat_enabled: boolean
    recording_enabled: boolean
    quality: '720p' | '1080p'
    max_viewers?: number
  }
  analytics: {
    peak_viewers: number
    total_watch_time: number
    chat_messages: number
    recording_duration?: number
  }
  created_by: string
  created_at: Date
  updated_at: Date
}
```

### Roku Channel Management

```typescript
// Roku channel configuration
interface RokuChannel {
  id: string
  tenant_id: string
  channel_name: string
  channel_description: string
  developer_credentials: {
    developer_id: string
    api_key_encrypted: string // Encrypted storage
    package_name: string
  }
  branding: {
    poster_art_url: string // 540x405
    splash_screen_url: string // 1280x720
    category_images: {
      [category: string]: string
    }
  }
  content_settings: {
    featured_playlist_id?: string
    categories: RokuCategory[]
    auto_sync: boolean
    sync_frequency: 'hourly' | 'daily' | 'weekly'
  }
  submission_status: {
    status: 'draft' | 'submitted' | 'approved' | 'rejected' | 'published'
    submitted_at?: Date
    feedback?: string
    version: string
  }
  analytics_enabled: boolean
  created_at: Date
  updated_at: Date
}

// Roku content category
interface RokuCategory {
  id: string
  name: string
  description?: string
  playlist_id?: string
  sort_order: number
  enabled: boolean
}

// Roku content feed (JSON structure)
interface RokuContentFeed {
  providerName: string
  lastUpdated: string
  language: string
  categories: RokuFeedCategory[]
  playlists: RokuFeedPlaylist[]
  series: RokuFeedSeries[]
  movies: RokuFeedMovie[]
}

interface RokuFeedCategory {
  name: string
  description: string
  playlistName: string
  order: number
}

interface RokuFeedPlaylist {
  name: string
  itemIds: string[]
}

interface RokuFeedMovie {
  id: string
  title: string
  content: {
    dateAdded: string
    videos: Array<{
      url: string
      quality: 'HD' | 'FHD'
      videoType: 'MP4' | 'HLS'
    }>
    duration: number
  }
  thumbnail: string
  episodeNumber?: number
  description: string
  genres: string[]
  tags: string[]
  credits: Array<{
    role: string
    name: string
  }>
  releaseDate: string
  rating: {
    rating: string
    ratingSource: string
  }
}
```

### Analytics & Reporting

```typescript
// Platform analytics
interface PlatformAnalytics {
  tenant_id: string
  date_range: {
    start_date: Date
    end_date: Date
  }
  video_metrics: {
    total_videos: number
    total_views: number
    total_watch_time: number // in minutes
    average_engagement: number // percentage
    popular_videos: Array<{
      video_id: string
      title: string
      views: number
      watch_time: number
    }>
  }
  event_metrics: {
    total_events: number
    live_streams_conducted: number
    average_attendance: number
    peak_concurrent_viewers: number
  }
  roku_metrics?: {
    channel_installs: number
    content_views: number
    user_engagement: number
    popular_content: Array<{
      content_id: string
      title: string
      views: number
    }>
  }
  user_activity: {
    active_users: number
    content_uploads: number
    events_created: number
  }
}

// Individual video analytics
interface VideoAnalytics {
  video_id: string
  metrics: {
    total_views: number
    unique_viewers: number
    watch_time: number // total minutes watched
    completion_rate: number // percentage who watched to end
    engagement_points: Array<{
      timestamp: number // seconds
      engagement_score: number // 0-100
    }>
    geographic_data: Array<{
      country: string
      views: number
    }>
    device_data: Array<{
      device_type: 'web' | 'mobile' | 'roku' | 'tablet'
      views: number
    }>
  }
  time_series: Array<{
    date: Date
    views: number
    watch_time: number
  }>
}
```

## Inspinia Integration Models

### Dashboard Components

```typescript
// Extends Inspinia's StatCard for church metrics
interface ChurchMetricCard {
  id: number
  title: string
  value: number | string
  suffix?: string
  prefix?: string
  badgeText: string
  badgeVariant: 'primary' | 'secondary' | 'success' | 'info' | 'warning' | 'danger' | 'light'
  icon: React.ComponentType
  pointColor: string
  description: string
  total: string
  metric_type: 'video' | 'playlist' | 'event' | 'user' | 'view' | 'engagement'
  tenant_id: string
}

// Activity feed extending Inspinia's timeline pattern
interface ChurchActivity {
  id: string
  tenant_id: string
  type: 'video_upload' | 'playlist_create' | 'event_schedule' | 'user_join' | 'stream_start'
  title: string
  description: string
  user_name: string
  user_avatar?: string
  timestamp: Date
  icon: React.ComponentType
  icon_color: string
  tag: string
  tag_variant: string
  metadata?: Record<string, any>
}
```

### Table Data Models

```typescript
// Video table row for Inspinia DataTable
interface VideoTableRow {
  id: string
  title: string
  thumbnail: string
  duration: string // formatted duration
  upload_date: Date
  status: {
    label: string
    variant: 'success' | 'warning' | 'danger' | 'info'
  }
  views: number
  actions: Array<{
    label: string
    icon: React.ComponentType
    onClick: () => void
    variant?: string
  }>
}

// Playlist table row
interface PlaylistTableRow {
  id: string
  name: string
  thumbnail: string
  video_count: number
  type: string
  created_date: Date
  roku_enabled: boolean
  actions: Array<{
    label: string
    icon: React.ComponentType
    onClick: () => void
  }>
}
```

### Form Data Models

```typescript
// Video upload form data
interface VideoUploadForm {
  title: string
  description?: string
  speaker?: string
  series?: string
  scripture_reference?: string
  tags: string[]
  language: string
  recorded_at?: Date
  thumbnail?: File
  roku_enabled: boolean
  roku_category?: string
  roku_featured: boolean
}

// Event creation form data
interface EventForm {
  title: string
  description?: string
  type: 'service' | 'special_event' | 'conference' | 'meeting'
  start_time: Date
  end_time: Date
  timezone: string
  location_name?: string
  location_address?: string
  location_room?: string
  live_stream_enabled: boolean
  registration_required: boolean
  max_attendees?: number
  recurrence_type?: 'none' | 'weekly' | 'monthly'
  recurrence_interval?: number
  recurrence_end_date?: Date
  recurrence_days?: number[]
}
```

## API Response Types

```typescript
// Standard API response wrapper
interface ApiResponse<T> {
  data: T
  message?: string
  errors?: string[]
  pagination?: {
    current_page: number
    per_page: number
    total: number
    total_pages: number
    has_next_page: boolean
    has_prev_page: boolean
  }
}

// API error response
interface ApiError {
  message: string
  errors?: Record<string, string[]>
  status_code: number
}

// File upload response
interface FileUploadResponse {
  file_id: string
  file_url: string
  thumbnail_url?: string
  file_size: number
  mime_type: string
  upload_status: 'processing' | 'complete' | 'error'
  processing_progress?: number
}
```

## Validation Schemas (Zod)

```typescript
import { z } from 'zod'

// Video upload validation
export const VideoUploadSchema = z.object({
  title: z.string().min(1, 'Title is required').max(200, 'Title too long'),
  description: z.string().max(1000, 'Description too long').optional(),
  speaker: z.string().max(100, 'Speaker name too long').optional(),
  series: z.string().max(100, 'Series name too long').optional(),
  scripture_reference: z.string().max(200, 'Scripture reference too long').optional(),
  tags: z.array(z.string()).max(10, 'Too many tags'),
  language: z.string().min(2, 'Language required'),
  recorded_at: z.date().optional(),
  roku_enabled: z.boolean().default(false),
  roku_category: z.string().optional(),
  roku_featured: z.boolean().default(false)
})

// Event creation validation
export const EventFormSchema = z.object({
  title: z.string().min(1, 'Title is required').max(200, 'Title too long'),
  description: z.string().max(1000, 'Description too long').optional(),
  type: z.enum(['service', 'special_event', 'conference', 'meeting']),
  start_time: z.date(),
  end_time: z.date(),
  timezone: z.string().min(1, 'Timezone required'),
  location_name: z.string().max(200, 'Location name too long').optional(),
  live_stream_enabled: z.boolean().default(false),
  registration_required: z.boolean().default(false),
  max_attendees: z.number().min(1).optional()
}).refine(data => data.end_time > data.start_time, {
  message: 'End time must be after start time',
  path: ['end_time']
})

// Tenant branding validation
export const TenantBrandingSchema = z.object({
  primary_color: z.string().regex(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/, 'Invalid color format'),
  secondary_color: z.string().regex(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/, 'Invalid color format'),
  logo_url: z.string().url('Invalid logo URL').optional(),
  favicon_url: z.string().url('Invalid favicon URL').optional(),
  font_family: z.string().max(100, 'Font family name too long').optional()
})
```

These data models provide a comprehensive foundation for the Church Media Platform frontend, ensuring type safety throughout the application while maintaining compatibility with both Inspinia components and the Node.js backend API.