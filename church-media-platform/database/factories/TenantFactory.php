<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $churchName = $this->faker->company() . ' Church';
        $subdomain = strtolower(str_replace([' ', "'", '.'], ['', '', ''], $churchName));
        
        return [
            'name' => $churchName,
            'subdomain' => $subdomain . $this->faker->randomNumber(2),
            'email' => $this->faker->safeEmail(),
            'status' => 'active',
            'branding_settings' => [
                'logo_url' => $this->faker->imageUrl(200, 80, 'business'),
                'primary_color' => $this->faker->hexColor(),
                'secondary_color' => $this->faker->hexColor(),
                'background_color' => '#ffffff',
                'text_color' => '#000000',
            ],
            'feature_settings' => [
                'require_2fa' => $this->faker->boolean(30), // 30% chance
                'max_users' => $this->faker->numberBetween(5, 50),
                'max_videos' => $this->faker->numberBetween(100, 1000),
                'allow_live_streaming' => $this->faker->boolean(80),
                'custom_branding' => true,
            ],
            'trial_ends_at' => $this->faker->optional(0.3)->dateTimeBetween('+1 week', '+1 month'),
            'subscription_ends_at' => $this->faker->optional(0.7)->dateTimeBetween('+1 month', '+1 year'),
        ];
    }

    /**
     * Configure the factory to create a tenant that requires 2FA
     */
    public function requires2FA(): static
    {
        return $this->state(function (array $attributes) {
            $attributes['feature_settings']['require_2fa'] = true;
            return $attributes;
        });
    }

    /**
     * Configure the factory to create an inactive tenant
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'inactive',
            ];
        });
    }

    /**
     * Configure the factory to create a trial tenant
     */
    public function onTrial(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'trial_ends_at' => now()->addDays(14),
                'subscription_ends_at' => null,
            ];
        });
    }
}