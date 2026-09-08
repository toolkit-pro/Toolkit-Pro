<?php

namespace Database\Factories;

use App\Models\Tool;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tool>
 */
class ToolFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tool::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(3),
            'instructions' => fake()->paragraphs(2, true),
            
            'icon' => null,
            'thumbnail' => null,
            'screenshot' => null,
            'video_url' => fake()->optional()->url(),
            
            'url' => fake()->optional()->url(),
            'api_url' => fake()->optional()->url(),
            'documentation_url' => fake()->optional()->url(),
            'support_url' => fake()->optional()->url(),
            
            'version' => fake()->semver(),
            'status' => fake()->randomElement(['active', 'active', 'active', 'pending', 'inactive']),
            
            'is_featured' => fake()->boolean(20),
            'is_premium' => fake()->boolean(30),
            'is_free' => fake()->boolean(80),
            'is_verified' => fake()->boolean(70),
            'is_approved' => fake()->boolean(80),
            
            'price' => fake()->randomFloat(2, 0, 100),
            'currency' => fake()->randomElement(['USD', 'BDT', 'EUR', 'GBP']),
            'billing_cycle' => fake()->optional()->randomElement(['one_time', 'monthly', 'yearly']),
            'discount_price' => fake()->optional()->randomFloat(2, 0, 50),
            'discount_ends_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            
            'creator_id' => User::factory(),
            
            'views_count' => fake()->numberBetween(0, 1000000),
            'uses_count' => fake()->numberBetween(0, 500000),
            'downloads_count' => fake()->numberBetween(0, 100000),
            'favorites_count' => fake()->numberBetween(0, 50000),
            'shares_count' => fake()->numberBetween(0, 20000),
            'comments_count' => fake()->numberBetween(0, 5000),
            
            'rating' => fake()->randomFloat(2, 0, 5),
            'rating_count' => fake()->numberBetween(0, 10000),
            
            'average_time_spent' => fake()->numberBetween(30, 3600),
            'success_rate' => fake()->numberBetween(70, 100),
            'error_rate' => fake()->numberBetween(0, 10),
            
            'tags' => fake()->randomElements([
                'calculator', 'converter', 'editor', 'generator', 'analyzer',
                'optimizer', 'compressor', 'validator', 'formatter', 'viewer',
                'downloader', 'uploader', 'scanner', 'tracker', 'manager',
            ], fake()->numberBetween(2, 5)),
            
            'requirements' => [
                'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                'internet' => fake()->boolean(80),
                'account' => fake()->boolean(30),
            ],
            
            'compatibility' => [
                'desktop' => true,
                'tablet' => fake()->boolean(80),
                'mobile' => fake()->boolean(70),
            ],
            
            'languages' => fake()->randomElements([
                'bn', 'en', 'hi', 'ar', 'es', 'fr', 'de', 'pt', 'ru', 'zh', 'ja', 'ko',
            ], fake()->numberBetween(1, 5)),
            
            'metadata' => [
                'skill_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced', 'expert']),
                'popularity_score' => fake()->numberBetween(0, 100),
                'last_used_at' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            ],
            
            'meta_title' => $name,
            'meta_description' => fake()->sentence(10),
            'meta_keywords' => implode(', ', fake()->words(5)),
            
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'deleted_at' => null,
        ];
    }

    /**
     * Indicate that the tool is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the tool is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the tool is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the tool is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Indicate that the tool is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }

    /**
     * Indicate that the tool is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the tool is premium.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_premium' => true,
            'is_free' => false,
            'price' => fake()->randomFloat(2, 10, 500),
        ]);
    }

    /**
     * Indicate that the tool is free.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_free' => true,
            'is_premium' => false,
            'price' => 0,
        ]);
    }

    /**
     * Indicate that the tool is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'is_approved' => true,
        ]);
    }

    /**
     * Indicate that the tool is popular.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'views_count' => fake()->numberBetween(100000, 1000000),
            'uses_count' => fake()->numberBetween(50000, 500000),
            'favorites_count' => fake()->numberBetween(10000, 50000),
            'rating' => fake()->randomFloat(2, 4, 5),
            'rating_count' => fake()->numberBetween(1000, 10000),
        ]);
    }

    /**
     * Indicate that the tool is new.
     */
    public function new(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Indicate that the tool is trending.
     */
    public function trending(): static
    {
        return $this->state(fn (array $attributes) => [
            'uses_count' => fake()->numberBetween(10000, 100000),
            'views_count' => fake()->numberBetween(50000, 500000),
            'rating_count' => fake()->numberBetween(500, 5000),
            'rating' => fake()->randomFloat(2, 3.5, 5),
        ]);
    }

    /**
     * Indicate that the tool is for beginners.
     */
    public function beginnerLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'skill_level' => 'beginner',
            ]),
        ]);
    }

    /**
     * Indicate that the tool is for experts.
     */
    public function expertLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'skill_level' => 'expert',
            ]),
        ]);
    }

    /**
     * Indicate that the tool has high rating.
     */
    public function highRated(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->randomFloat(2, 4.5, 5),
            'rating_count' => fake()->numberBetween(100, 10000),
        ]);
    }

    /**
     * Indicate that the tool has low rating.
     */
    public function lowRated(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->randomFloat(2, 1, 2.5),
            'rating_count' => fake()->numberBetween(10, 100),
        ]);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Tool $tool) {
            // মেকিং এর পরে কিছু করুন
        })->afterCreating(function (Tool $tool) {
            // ক্রিয়েটিং এর পরে কিছু করুন
        });
    }
}
