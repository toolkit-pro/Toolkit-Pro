<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, WithFaker;

    /**
     * Indicates whether the default seeder should run before each test.
     *
     * @var bool
     */
    protected $seed = false;

    /**
     * The authenticated user.
     *
     * @var User|null
     */
    protected $user;

    /**
     * The admin user.
     *
     * @var User|null
     */
    protected $admin;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // টেস্টিং এনভায়রনমেন্ট সেটআপ
        $this->setupTestEnvironment();
        
        // ক্যাশ ক্লিয়ার
        $this->clearCache();
        
        // স্টোরেজ মক
        $this->setupStorage();
        
        // টেস্ট হেল্পার
        $this->setupTestHelpers();
    }

    /**
     * Tear down the test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // ক্যাশ ক্লিয়ার
        $this->clearCache();
        
        // স্টোরেজ ক্লিয়ার
        Storage::fake('public');
        
        parent::tearDown();
    }

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setupTestEnvironment(): void
    {
        // টেস্টিং ডাটাবেস
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        
        // ক্যাশ ড্রাইভার
        config(['cache.default' => 'array']);
        
        // সেশন ড্রাইভার
        config(['session.driver' => 'array']);
        
        // কিউ ড্রাইভার
        config(['queue.default' => 'sync']);
        
        // মেইল ড্রাইভার
        config(['mail.default' => 'array']);
    }

    /**
     * Clear all cache.
     *
     * @return void
     */
    protected function clearCache(): void
    {
        Cache::flush();
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
    }

    /**
     * Setup storage for testing.
     *
     * @return void
     */
    protected function setupStorage(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::fake('s3');
    }

    /**
     * Setup test helpers.
     *
     * @return void
     */
    protected function setupTestHelpers(): void
    {
        // ডিফল্ট ইউজার তৈরি
        $this->user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->user->assignRole('user');
        
        // ডিফল্ট অ্যাডমিন তৈরি
        $this->admin = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('admin');
    }

    /**
     * Act as authenticated user.
     *
     * @param User|null $user
     * @return $this
     */
    protected function actingAsUser(?User $user = null): self
    {
        $user = $user ?? $this->user;
        Sanctum::actingAs($user, ['*']);
        
        return $this;
    }

    /**
     * Act as admin user.
     *
     * @param User|null $admin
     * @return $this
     */
    protected function actingAsAdmin(?User $admin = null): self
    {
        $admin = $admin ?? $this->admin;
        Sanctum::actingAs($admin, ['*']);
        
        return $this;
    }

    /**
     * Act as super admin.
     *
     * @return $this
     */
    protected function actingAsSuperAdmin(): self
    {
        $superAdmin = User::factory()->create([
            'status' => 'active',
        ]);
        $superAdmin->assignRole('super-admin');
        
        Sanctum::actingAs($superAdmin, ['*']);
        
        return $this;
    }

    /**
     * Create a user with specific role.
     *
     * @param string $role
     * @param array $attributes
     * @return User
     */
    protected function createUserWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'status' => 'active',
            'email_verified_at' => now(),
        ], $attributes));
        
        $user->assignRole($role);
        
        return $user;
    }

    /**
     * Create roles and permissions for testing.
     *
     * @return void
     */
    protected function setupRolesAndPermissions(): void
    {
        // রোল তৈরি
        $roles = ['super-admin', 'admin', 'moderator', 'editor', 'user', 'premium-user'];
        
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
        
        // পারমিশন তৈরি
        $permissions = [
            'view-users', 'create-users', 'edit-users', 'delete-users',
            'view-tools', 'create-tools', 'edit-tools', 'delete-tools',
            'view-categories', 'create-categories', 'edit-categories', 'delete-categories',
            'view-reports', 'create-reports', 'download-reports',
            'view-analytics', 'view-settings', 'edit-settings',
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
        // সুপার অ্যাডমিনকে সব পারমিশন দিন
        Role::findByName('super-admin')->givePermissionTo(Permission::all());
        
        // অ্যাডমিনকে নির্দিষ্ট পারমিশন দিন
        Role::findByName('admin')->givePermissionTo([
            'view-users', 'create-users', 'edit-users',
            'view-tools', 'create-tools', 'edit-tools', 'delete-tools',
            'view-categories', 'create-categories', 'edit-categories',
            'view-reports', 'create-reports',
            'view-analytics', 'view-settings',
        ]);
    }

    /**
     * Get JSON response data.
     *
     * @param mixed $response
     * @return array
     */
    protected function getResponseData($response): array
    {
        return $response->json();
    }

    /**
     * Assert successful response.
     *
     * @param mixed $response
     * @return void
     */
    protected function assertSuccessResponse($response): void
    {
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Assert error response.
     *
     * @param mixed $response
     * @param int $statusCode
     * @return void
     */
    protected function assertErrorResponse($response, int $statusCode = 400): void
    {
        $response->assertStatus($statusCode)
            ->assertJson(['success' => false]);
    }

    /**
     * Assert validation error response.
     *
     * @param mixed $response
     * @return void
     */
    protected function assertValidationErrorResponse($response): void
    {
        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'error' => [
                    'errors',
                ],
            ]);
    }

    /**
     * Generate random tool data.
     *
     * @param array $overrides
     * @return array
     */
    protected function generateToolData(array $overrides = []): array
    {
        return array_merge([
            'name' => $this->faker->unique()->company . ' Tool',
            'description' => $this->faker->sentence(10),
            'instructions' => $this->faker->paragraph(),
            'is_free' => true,
            'status' => 'active',
            'tags' => ['test', 'tool'],
            'category_ids' => [1],
        ], $overrides);
    }

    /**
     * Generate random category data.
     *
     * @param array $overrides
     * @return array
     */
    protected function generateCategoryData(array $overrides = []): array
    {
        return array_merge([
            'name' => $this->faker->unique()->word,
            'description' => $this->faker->sentence(),
            'status' => 'active',
        ], $overrides);
    }

    /**
     * Generate random automation data.
     *
     * @param array $overrides
     * @return array
     */
    protected function generateAutomationData(array $overrides = []): array
    {
        return array_merge([
            'name' => $this->faker->unique()->word . ' Automation',
            'trigger' => [
                'type' => 'schedule',
                'time' => '09:00',
            ],
            'action' => [
                'type' => 'notify',
                'message' => 'Test notification',
            ],
        ], $overrides);
    }

    /**
     * Assert database has record.
     *
     * @param string $table
     * @param array $data
     * @return void
     */
    protected function assertDatabaseHasRecord(string $table, array $data): void
    {
        $this->assertDatabaseHas($table, $data);
    }

    /**
     * Assert database missing record.
     *
     * @param string $table
     * @param array $data
     * @return void
     */
    protected function assertDatabaseMissingRecord(string $table, array $data): void
    {
        $this->assertDatabaseMissing($table, $data);
    }

    /**
     * Get authenticated token.
     *
     * @param User $user
     * @return string
     */
    protected function getToken(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    /**
     * Get auth headers.
     *
     * @param User $user
     * @return array
     */
    protected function getAuthHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getToken($user),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Make API request with auth.
     *
     * @param User $user
     * @param string $method
     * @param string $url
     * @param array $data
     * @return mixed
     */
    protected function apiRequest(User $user, string $method, string $url, array $data = [])
    {
        return $this->withHeaders($this->getAuthHeaders($user))
            ->json($method, $url, $data);
    }

    /**
     * Get API URL.
     *
     * @param string $path
     * @return string
     */
    protected function apiUrl(string $path): string
    {
        return '/api/v1/' . ltrim($path, '/');
    }
}
