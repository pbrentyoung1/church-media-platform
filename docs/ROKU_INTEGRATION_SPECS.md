# Roku Integration Specifications

## Overview

This document outlines the complete integration between the Church Media Platform and Roku channel development, based on analysis of Roku's official developer resources and SceneGraph architecture patterns.

## Roku Channel Architecture

### SceneGraph Application Structure
Based on the `scenegraph-master-sample` repository analysis:

```
Roku Channel Components:
├── manifest (Channel configuration)
├── components/
│   ├── ContentGrid.brs         # Video grid display
│   ├── DetailsScreen.brs       # Video details and playback
│   ├── VideoPlayer.brs         # Media playback controls
│   └── CategoryList.brs        # Content categorization
├── source/
│   ├── main.brs               # Entry point
│   ├── utils.brs              # Utility functions
│   └── api.brs                # Content feed parsing
└── images/
    ├── splash_hd.jpg          # Channel splash screen
    ├── poster_art.jpg         # Channel poster
    └── category_icons/        # Category imagery
```

## Church Media Platform → Roku Content Pipeline

### 1. Content Feed Generation

#### JSON Feed Structure (Roku Direct Publisher Format)
```typescript
interface RokuContentFeed {
  providerName: string          // Church name
  lastUpdated: string          // ISO 8601 timestamp
  language: string             // "en-us", "es", etc.
  categories: RokuCategory[]   // Content organization
  playlists: RokuPlaylist[]    // Sermon series, collections
  series: RokuSeries[]         // Multi-part content
  movies: RokuMovie[]          // Individual videos
}

interface RokuMovie {
  id: string                   // UUID from church platform
  title: string               // Video title
  content: {
    dateAdded: string         // Upload date
    videos: Array<{
      url: string             // Direct video URL
      quality: "HD" | "FHD"   // Video resolution
      videoType: "MP4" | "HLS" // Video format
    }>
    duration: number          // Seconds
    language: string          // Content language
    captionFiles?: Array<{    // Optional subtitles
      url: string
      language: string
    }>
  }
  thumbnail: string           // Thumbnail image URL
  shortDescription: string    // Brief description
  longDescription: string     // Detailed description
  genres: string[]            // ["Religious", "Educational"]
  tags: string[]              // Sermon tags, topics
  credits: Array<{            // Speaker information
    role: "speaker" | "host"
    name: string
  }>
  releaseDate: string         // Original recording date
  rating: {
    rating: "NR"              // Not Rated for religious content
    ratingSource: "CUSTOM"
  }
  // Church-specific metadata
  episodeNumber?: number      // For series
  seriesName?: string         // Sermon series name
  scriptureReference?: string // Bible verses
}

interface RokuCategory {
  name: string                // "Sunday Services", "Bible Studies"
  description: string
  playlistName: string        // Associated playlist
  order: number              // Display order
}

interface RokuPlaylist {
  name: string               // Playlist identifier
  itemIds: string[]          // Video IDs in playlist
}
```

#### Content Feed Generator (Church Media Platform)
```typescript
class RokuContentFeedGenerator {
  constructor(
    private tenant: Tenant,
    private videos: Video[],
    private playlists: Playlist[]
  ) {}

  generateFeed(): RokuContentFeed {
    return {
      providerName: this.tenant.name,
      lastUpdated: new Date().toISOString(),
      language: "en-us", // TODO: Support tenant language settings
      categories: this.generateCategories(),
      playlists: this.generatePlaylists(),
      series: this.generateSeries(),
      movies: this.generateMovies()
    }
  }

  private generateMovies(): RokuMovie[] {
    return this.videos
      .filter(video => video.status === 'ready' && video.roku_settings.enabled)
      .map(video => ({
        id: video.id,
        title: video.title,
        content: {
          dateAdded: video.created_at.toISOString(),
          videos: [{
            url: this.getDirectVideoUrl(video.file_url),
            quality: video.resolution === '1080p' ? 'FHD' : 'HD',
            videoType: 'MP4'
          }],
          duration: video.duration,
          language: video.metadata.language || 'en'
        },
        thumbnail: this.getOptimizedThumbnail(video.thumbnail_url, '16x9'),
        shortDescription: this.truncateDescription(video.description, 200),
        longDescription: video.description || video.title,
        genres: this.getVideoGenres(video),
        tags: video.metadata.tags || [],
        credits: video.metadata.speaker ? [{
          role: 'speaker',
          name: video.metadata.speaker
        }] : [],
        releaseDate: (video.metadata.recorded_at || video.created_at).toISOString(),
        rating: {
          rating: "NR",
          ratingSource: "CUSTOM"
        },
        // Church-specific fields
        seriesName: video.metadata.series,
        scriptureReference: video.metadata.scripture_reference
      }))
  }

  private generateCategories(): RokuCategory[] {
    const categories = [
      {
        name: "Featured",
        description: "Featured church content",
        playlistName: "featured",
        order: 1
      },
      {
        name: "Sunday Services",
        description: "Weekly worship services",
        playlistName: "sunday-services",
        order: 2
      },
      {
        name: "Bible Studies",
        description: "In-depth biblical teaching",
        playlistName: "bible-studies", 
        order: 3
      },
      {
        name: "Special Events",
        description: "Holiday and special services",
        playlistName: "special-events",
        order: 4
      }
    ]

    return categories
  }
}
```

### 2. Content Delivery & CDN Integration

#### Video URL Optimization for Roku
```typescript
interface VideoDeliveryConfig {
  // Direct CDN URLs for optimal Roku performance
  baseUrl: string              // CDN base URL
  resolutions: {
    '720p': string            // HD version URL
    '1080p': string           // FHD version URL
  }
  formats: {
    mp4: string               // MP4 for direct playback
    hls?: string              // HLS for adaptive streaming (optional)
  }
  thumbnails: {
    '16x9_sd': string         // 480x270 thumbnail
    '16x9_hd': string         // 1280x720 thumbnail
  }
}

class VideoDeliveryOptimizer {
  static generateRokuUrls(video: Video, cdnConfig: CDNConfig): VideoDeliveryConfig {
    const baseUrl = `${cdnConfig.baseUrl}/${video.tenant_id}/videos/${video.id}`
    
    return {
      baseUrl,
      resolutions: {
        '720p': `${baseUrl}/720p.mp4`,
        '1080p': `${baseUrl}/1080p.mp4`
      },
      formats: {
        mp4: `${baseUrl}/${video.resolution || '720p'}.mp4`
      },
      thumbnails: {
        '16x9_sd': `${baseUrl}/thumbnail_480x270.jpg`,
        '16x9_hd': `${baseUrl}/thumbnail_1280x720.jpg`
      }
    }
  }
}
```

### 3. Channel Management Interface (Inspinia Integration)

#### Roku Channel Dashboard
```typescript
// Built on Inspinia's dashboard components
const RokuChannelDashboard = () => {
  const { data: channelStats } = useQuery(['roku-stats'], fetchRokuAnalytics)
  
  return (
    <Container fluid>
      {/* Channel Status Cards - Using Inspinia StatCards */}
      <Row>
        <Col lg={3}>
          <StatCard
            title="Channel Status"
            value={channel.submission_status.status}
            badgeText={channel.submission_status.version}
            badgeVariant="success"
            icon={TbDeviceTv}
            pointColor="success"
            description="Current Status"
            total={`Updated ${formatDate(channel.updated_at)}`}
          />
        </Col>
        <Col lg={3}>
          <StatCard
            title="Content Items"
            value={channelStats.contentCount}
            badgeText={`+${channelStats.newThisWeek} This Week`}
            badgeVariant="primary"
            icon={TbPlay}
            pointColor="primary"
            description="Published Content"
            total={`${channelStats.totalViews} Total Views`}
          />
        </Col>
        <Col lg={3}>
          <StatCard
            title="Channel Installs"
            value={channelStats.installCount}
            badgeText={`+${channelStats.newInstalls} New`}
            badgeVariant="info"
            icon={TbDownload}
            pointColor="info"
            description="Active Installs"
            total={`${channelStats.activeUsers} Active Users`}
          />
        </Col>
        <Col lg={3}>
          <StatCard
            title="Content Views"
            value={channelStats.totalViews}
            badgeText={`+${channelStats.viewGrowth}%`}
            badgeVariant="success"
            icon={TbEye}
            pointColor="success"
            description="Total Views"
            total={`${channelStats.watchTime}h Watch Time`}
          />
        </Col>
      </Row>

      {/* Content Feed Management - Using Inspinia DataTable */}
      <Row>
        <Col lg={8}>
          <Card>
            <Card.Header>
              <h5 className="mb-0">Content Feed Management</h5>
            </Card.Header>
            <Card.Body>
              <DataTable
                table={rokuContentTable}
                emptyMessage="No content available for Roku channel"
              />
            </Card.Body>
          </Card>
        </Col>
        <Col lg={4}>
          <RokuChannelControls channel={channel} />
        </Col>
      </Row>
    </Container>
  )
}
```

#### Content Feed Management Table
```typescript
const rokuContentColumns: ColumnDef<Video>[] = [
  {
    accessorKey: 'thumbnail_url',
    header: 'Thumbnail',
    cell: ({ row }) => (
      <img 
        src={row.getValue('thumbnail_url')} 
        alt="Content thumbnail"
        className="avatar-sm rounded"
        style={{ aspectRatio: '16/9' }}
      />
    )
  },
  {
    accessorKey: 'title',
    header: 'Content Title',
    cell: ({ row }) => (
      <div>
        <div className="fw-semibold">{row.getValue('title')}</div>
        <div className="text-muted small">
          {row.original.metadata.series && (
            <span className="badge bg-light text-dark me-1">
              {row.original.metadata.series}
            </span>
          )}
          {row.original.metadata.speaker}
        </div>
      </div>
    )
  },
  {
    accessorKey: 'roku_settings.category',
    header: 'Roku Category'
  },
  {
    accessorKey: 'roku_settings.featured',
    header: 'Featured',
    cell: ({ row }) => (
      <Form.Check
        checked={row.getValue('roku_settings.featured')}
        onChange={(e) => handleFeatureToggle(row.original.id, e.target.checked)}
        disabled={!row.original.roku_settings.enabled}
      />
    )
  },
  {
    accessorKey: 'analytics.view_count',
    header: 'Roku Views',
    cell: ({ row }) => (
      <div>
        <div className="fw-semibold">{row.getValue('analytics.view_count')}</div>
        <small className="text-muted">Total views</small>
      </div>
    )
  },
  {
    accessorKey: 'roku_settings.enabled',
    header: 'Status',
    cell: ({ row }) => {
      const enabled = row.getValue('roku_settings.enabled')
      return (
        <Badge bg={enabled ? 'success' : 'secondary'}>
          {enabled ? 'Published' : 'Disabled'}
        </Badge>
      )
    }
  },
  {
    id: 'actions',
    header: 'Actions',
    cell: ({ row }) => (
      <Dropdown>
        <Dropdown.Toggle variant="light" size="sm">
          <TbDotsVertical />
        </Dropdown.Toggle>
        <Dropdown.Menu>
          <Dropdown.Item onClick={() => handleRokuToggle(row.original.id)}>
            {row.original.roku_settings.enabled ? 'Disable for Roku' : 'Enable for Roku'}
          </Dropdown.Item>
          <Dropdown.Item onClick={() => handleEditRokuSettings(row.original.id)}>
            Edit Roku Settings
          </Dropdown.Item>
          <Dropdown.Item onClick={() => handleUpdateFeed()}>
            Update Content Feed
          </Dropdown.Item>
        </Dropdown.Menu>
      </Dropdown>
    )
  }
]
```

### 4. Channel Configuration & Branding

#### Channel Settings Form (Inspinia Form Components)
```typescript
const RokuChannelSettingsForm = () => {
  const form = useForm<RokuChannelSettings>({
    resolver: zodResolver(RokuChannelSchema)
  })

  return (
    <Card>
      <Card.Header>
        <h5 className="mb-0">Channel Configuration</h5>
      </Card.Header>
      <Card.Body>
        <Form onSubmit={form.handleSubmit(onSubmit)}>
          <Row>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Channel Name *</Form.Label>
                <Form.Control
                  {...form.register('channel_name')}
                  placeholder="Enter channel name"
                  isInvalid={!!form.formState.errors.channel_name}
                />
                <Form.Control.Feedback type="invalid">
                  {form.formState.errors.channel_name?.message}
                </Form.Control.Feedback>
              </Form.Group>

              <Form.Group className="mb-3">
                <Form.Label>Channel Description</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={3}
                  {...form.register('channel_description')}
                  placeholder="Describe your church channel"
                />
              </Form.Group>

              <Form.Group className="mb-3">
                <Form.Label>Developer ID *</Form.Label>
                <Form.Control
                  {...form.register('developer_credentials.developer_id')}
                  placeholder="Your Roku Developer ID"
                  isInvalid={!!form.formState.errors.developer_credentials?.developer_id}
                />
              </Form.Group>
            </Col>

            <Col md={6}>
              {/* Channel Branding Section */}
              <h6 className="mb-3">Channel Branding</h6>
              
              <Form.Group className="mb-3">
                <Form.Label>Channel Poster Art (540x405px)</Form.Label>
                <FileUploader
                  accept={{ 'image/*': ['.jpg', '.png'] }}
                  maxSize={1024 * 1024} // 1MB
                  onUpload={handlePosterUpload}
                />
                {form.watch('branding.poster_art_url') && (
                  <div className="mt-2">
                    <img 
                      src={form.watch('branding.poster_art_url')}
                      alt="Channel poster"
                      className="img-thumbnail"
                      style={{ maxWidth: '200px' }}
                    />
                  </div>
                )}
              </Form.Group>

              <Form.Group className="mb-3">
                <Form.Label>Splash Screen (1280x720px)</Form.Label>
                <FileUploader
                  accept={{ 'image/*': ['.jpg', '.png'] }}
                  maxSize={1024 * 1024} // 1MB
                  onUpload={handleSplashUpload}
                />
              </Form.Group>
            </Col>
          </Row>

          <hr />

          {/* Content Categories Section */}
          <h6 className="mb-3">Content Categories</h6>
          <Row>
            {form.watch('content_settings.categories')?.map((category, index) => (
              <Col md={6} key={index} className="mb-3">
                <Card className="border">
                  <Card.Body>
                    <div className="d-flex justify-content-between align-items-start">
                      <div className="flex-grow-1">
                        <Form.Control
                          {...form.register(`content_settings.categories.${index}.name`)}
                          placeholder="Category name"
                          className="mb-2"
                        />
                        <Form.Select
                          {...form.register(`content_settings.categories.${index}.playlist_id`)}
                        >
                          <option value="">Select playlist...</option>
                          {playlists.map(playlist => (
                            <option key={playlist.id} value={playlist.id}>
                              {playlist.name}
                            </option>
                          ))}
                        </Form.Select>
                      </div>
                      <Button
                        variant="outline-danger"
                        size="sm"
                        onClick={() => removeCategory(index)}
                      >
                        <TbTrash />
                      </Button>
                    </div>
                  </Card.Body>
                </Card>
              </Col>
            ))}
            <Col md={12}>
              <Button 
                variant="outline-primary" 
                onClick={addCategory}
                className="w-100"
              >
                <TbPlus className="me-1" />Add Category
              </Button>
            </Col>
          </Row>

          <div className="d-flex gap-2">
            <Button type="submit" variant="primary">
              Save Configuration
            </Button>
            <Button 
              type="button" 
              variant="success" 
              onClick={generateContentFeed}
            >
              Generate Content Feed
            </Button>
            <Button 
              type="button" 
              variant="info" 
              onClick={testChannel}
            >
              Test Channel
            </Button>
          </div>
        </Form>
      </Card.Body>
    </Card>
  )
}
```

### 5. Content Feed API Endpoints

#### Feed Generation Service
```typescript
// Node.js backend service for Roku feed generation
class RokuFeedService {
  async generateContentFeed(tenantId: string): Promise<RokuContentFeed> {
    const tenant = await this.getTenant(tenantId)
    const videos = await this.getPublishedVideos(tenantId, { roku_enabled: true })
    const playlists = await this.getPlaylists(tenantId)
    
    const generator = new RokuContentFeedGenerator(tenant, videos, playlists)
    const feed = generator.generateFeed()
    
    // Cache feed for performance
    await this.cacheFeed(tenantId, feed)
    
    return feed
  }

  async serveFeed(tenantId: string): Promise<RokuContentFeed> {
    // Try cache first
    const cachedFeed = await this.getCachedFeed(tenantId)
    if (cachedFeed && !this.isExpired(cachedFeed)) {
      return cachedFeed
    }
    
    // Generate fresh feed
    return this.generateContentFeed(tenantId)
  }
}

// API Routes
app.get('/api/roku/:tenant_slug/feed.json', async (req, res) => {
  try {
    const tenant = await findTenantBySlug(req.params.tenant_slug)
    const feed = await rokuFeedService.serveFeed(tenant.id)
    
    res.setHeader('Content-Type', 'application/json')
    res.setHeader('Cache-Control', 'public, max-age=3600') // 1 hour cache
    res.json(feed)
  } catch (error) {
    res.status(500).json({ error: 'Feed generation failed' })
  }
})
```

### 6. Analytics Integration

#### Roku Analytics Dashboard (Inspinia Charts)
```typescript
const RokuAnalyticsDashboard = () => {
  const { data: analytics } = useQuery(['roku-analytics'], fetchRokuAnalytics)

  return (
    <Container fluid>
      <Row>
        <Col lg={6}>
          <Card className="mb-4">
            <Card.Header>
              <h5 className="mb-0">Channel Performance</h5>
            </Card.Header>
            <Card.Body>
              <CustomEChart 
                options={getChannelPerformanceOptions(analytics)}
                height="300px"
              />
            </Card.Body>
          </Card>
        </Col>
        
        <Col lg={6}>
          <Card className="mb-4">
            <Card.Header>
              <h5 className="mb-0">Content Popularity</h5>
            </Card.Header>
            <Card.Body>
              <CustomApexChart 
                options={getContentPopularityOptions(analytics)}
                height="300px"
              />
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Top Content Table */}
      <Row>
        <Col lg={12}>
          <Card>
            <Card.Header>
              <h5 className="mb-0">Top Performing Content</h5>
            </Card.Header>
            <Card.Body>
              <DataTable
                table={topContentTable}
                emptyMessage="No analytics data available"
              />
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </Container>
  )
}
```

### 7. Deployment & Certification Workflow

#### Channel Certification Checklist (Inspinia Components)
```typescript
const RokuCertificationChecklist = () => {
  const certificationItems = [
    {
      id: 'content_feed',
      title: 'Valid Content Feed',
      description: 'JSON feed validates against Roku schema',
      status: checkContentFeed(),
      required: true
    },
    {
      id: 'poster_art',
      title: 'Channel Poster Art',
      description: '540x405px JPG or PNG image',
      status: checkPosterArt(),
      required: true
    },
    {
      id: 'splash_screen',
      title: 'Splash Screen Image', 
      description: '1280x720px launch screen',
      status: checkSplashScreen(),
      required: true
    },
    {
      id: 'video_quality',
      title: 'Video Quality Standards',
      description: 'All videos meet Roku quality requirements',
      status: checkVideoQuality(),
      required: true
    },
    {
      id: 'content_rating',
      title: 'Content Rating Compliance',
      description: 'Appropriate ratings for all content',
      status: checkContentRatings(),
      required: false
    }
  ]

  return (
    <Card>
      <Card.Header>
        <h5 className="mb-0">Certification Checklist</h5>
      </Card.Header>
      <Card.Body>
        {certificationItems.map(item => (
          <div key={item.id} className="d-flex align-items-center mb-3">
            <div className="me-3">
              {item.status ? (
                <div className="text-success">
                  <TbCircleCheck size={24} />
                </div>
              ) : (
                <div className="text-warning">
                  <TbAlertCircle size={24} />
                </div>
              )}
            </div>
            <div className="flex-grow-1">
              <div className="fw-semibold">
                {item.title}
                {item.required && <span className="text-danger ms-1">*</span>}
              </div>
              <div className="text-muted small">{item.description}</div>
            </div>
            <div>
              <Badge bg={item.status ? 'success' : 'warning'}>
                {item.status ? 'Complete' : 'Pending'}
              </Badge>
            </div>
          </div>
        ))}
        
        <hr />
        
        <div className="d-flex justify-content-between align-items-center">
          <div>
            <strong>Overall Progress:</strong>
            <div className="progress mt-1" style={{ width: '200px' }}>
              <div 
                className="progress-bar"
                style={{ width: `${getCompletionPercentage()}%` }}
              />
            </div>
          </div>
          <Button 
            variant="primary"
            disabled={!allRequiredItemsComplete()}
            onClick={submitForCertification}
          >
            Submit for Certification
          </Button>
        </div>
      </Card.Body>
    </Card>
  )
}
```

## Implementation Benefits

### Development Efficiency
- **Roku Integration**: Complete content pipeline from CMS to channel
- **Professional Interface**: Enterprise-grade management tools
- **Automated Workflow**: Content feed generation and updates
- **Certification Support**: Built-in compliance checking

### Church Benefits
- **Multi-Platform Reach**: Web + Roku distribution
- **Professional Presence**: Branded Roku channel
- **Easy Management**: Intuitive content administration
- **Analytics Insights**: Comprehensive viewing data

### Technical Advantages
- **Scalable Architecture**: Supports multiple churches/channels
- **CDN Optimized**: Fast content delivery for Roku devices
- **Type Safety**: Full TypeScript integration
- **Proven Components**: Leverages Inspinia's battle-tested UI

This integration provides churches with a complete solution for managing their Roku channel presence while maintaining the professional quality and ease-of-use that Inspinia components provide.