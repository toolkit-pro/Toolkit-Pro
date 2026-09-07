<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->unique()->phoneNumber(),
            'phone_verified_at' => now(),
            'avatar' => null,
            'bio' => fake()->paragraph(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'postal_code' => fake()->postcode(),
            'date_of_birth' => fake()->date('Y-m-d', '-18 years'),
            'gender' => fake()->randomElement(['male', 'female', 'other', 'prefer_not_to_say']),
            'language' => fake()->randomElement(['bn', 'en', 'hi', 'ar']),
            'timezone' => fake()->timezone(),
            'currency' => fake()->randomElement(['USD', 'BDT', 'EUR', 'GBP']),
            'preferences' => [
                'theme' => fake()->randomElement(['light', 'dark']),
                'notifications' => [
                    'email' => fake()->boolean(80),
                    'push' => fake()->boolean(60),
                    'sms' => fake()->boolean(20),
                ],
                'privacy' => [
                    'show_profile' => fake()->boolean(70),
                    'show_email' => fake()->boolean(30),
                    'show_phone' => fake()->boolean(20),
                ],
            ],
            'status' => fake()->randomElement(['active', 'active', 'active', 'inactive', 'suspended']),
            'last_login_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'last_login_ip' => fake()->ipv4(),
            'two_factor_enabled' => fake()->boolean(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(10),
            'metadata' => [
                'skill_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced', 'expert']),
                'referral_source' => fake()->randomElement(['google', 'facebook', 'twitter', 'friend', 'other']),
                'signup_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            ],
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'deleted_at' => null,
        ];
    }

    /**
     * Indicate that the user is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the user is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the user is banned.
     */
    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'banned',
        ]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->afterCreating(function ($user) {
            $user->assignRole('admin');
        });
    }

    /**
     * Indicate that the user is a super admin.
     */
    public function superAdmin(): static
    {
        return $this->afterCreating(function ($user) {
            $user->assignRole('super-admin');
        });
    }

    /**
     * Indicate that the user is a premium user.
     */
    public function premium(): static
    {
        return $this->afterCreating(function ($user) {
            $user->assignRole('premium-user');
        });
    }

    /**
     * Indicate that the user has two-factor authentication enabled.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_enabled' => true,
            'two_factor_secret' => Str::random(32),
            'two_factor_recovery_codes' => json_encode([
                Str::random(10),
                Str::random(10),
                Str::random(10),
                Str::random(10),
                Str::random(10),
            ]),
        ]);
    }

    /**
     * Indicate that the user is from Bangladesh.
     */
    public function bangladeshi(): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => 'Bangladesh',
            'city' => fake()->randomElement(['Dhaka', 'Chittagong', 'Sylhet', 'Rajshahi', 'Khulna']),
            'language' => 'bn',
            'timezone' => 'Asia/Dhaka',
            'currency' => 'BDT',
            'phone' => '+8801' . fake()->numberBetween(3, 9) . fake()->numerify('########'),
        ]);
    }

    /**
     * Indicate that the user has dark theme preference.
     */
    public function darkTheme(): static
    {
        return $this->state(fn (array $attributes) => [
            'preferences' => array_merge($attributes['preferences'] ?? [], [
                'theme' => 'dark',
            ]),
        ]);
    }

    /**
     * Indicate that the user has verified phone.
     */
    public function phoneVerified(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => now(),
        ]);
    }

    /**
     * Indicate that the user has not verified phone.
     */
    public function phoneUnverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => null,
        ]);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function ($user) {
            // মেকিং এর পরে কিছু করুন
        })->afterCreating(function ($user) {
            // ক্রিয়েটিং এর পরে কিছু করুন
            $user->assignRole('user');
        });
    }
}
