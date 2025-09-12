<?php

namespace Database\Factories;

use App\Models\Video;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Video>
 */
class VideoFactory extends Factory
{
    protected $model = Video::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'duration' => $this->faker->numberBetween(60, 7200), // 1 minute to 2 hours in seconds
            'source' => $this->faker->randomElement(['vimeo', 'youtube', 'resi', 'upload']),
            'external_id' => $this->faker->unique()->numerify('########'),
            'playback_url' => $this->faker->url(),
            'thumbnail_url' => $this->faker->imageUrl(640, 360, 'abstract'),
            'captions_url' => $this->faker->optional(0.3)->url(),
            'status' => $this->faker->randomElement(['published', 'draft', 'archived']),
            'metadata' => [
                'resolution' => $this->faker->randomElement(['720p', '1080p', '4K']),
                'fps' => $this->faker->randomElement([24, 30, 60]),
                'codec' => 'H.264',
                'bitrate' => $this->faker->numberBetween(1000, 10000),
                'category' => $this->faker->randomElement(['sermon', 'worship', 'announcement', 'teaching', 'youth', 'children']),
            ],
            'synced_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Create a published video
     */
    public function published(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'published',
                'synced_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ];
        });
    }

    /**
     * Create a draft video
     */
    public function draft(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'draft',
                'synced_at' => null,
            ];
        });
    }

    /**
     * Create a video for a specific tenant
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(function (array $attributes) use ($tenant) {
            return [
                'tenant_id' => $tenant->id,
            ];
        });
    }
}