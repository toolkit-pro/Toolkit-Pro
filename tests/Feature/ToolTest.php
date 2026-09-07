<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tool;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ToolTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $admin;
    protected $tool;
    protected $category;

    /**
     * টেস্ট সেটআপ
     */
    protected function setUp(): void
    {
        parent::setUp();

        // ইউজার তৈরি
        $this->user = User::factory()->create([
            'status' => 'active',
        ]);
        $this->user->assignRole('user');

        // অ্যাডমিন তৈরি
        $this->admin = User::factory()->create([
            'status' => 'active',
        ]);
        $this->admin->assignRole('admin');

        // ক্যাটাগরি তৈরি
        $this->category = Category::factory()->create([
            'status' => 'active',
        ]);

        // টুল তৈরি
        $this->tool = Tool::factory()->create([
            'creator_id' => $this->user->id,
            'status' => 'active',
            'is_approved' => true,
        ]);
        $this->tool->categories()->attach($this->category->id);
    }

    /**
     * টুল তালিকা দেখা যাবে
     */
    public function test_can_list_tools(): void
    {
        $response = $this->getJson('/api/v1/tools');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Tools retrieved successfully',
            ]);
    }

    /**
     * টুল তৈরি করা যাবে
     */
    public function test_can_create_tool(): void
    {
        Sanctum::actingAs($this->user);

        $toolData = [
            'name' => 'Test Calculator',
            'description' => 'A test calculator tool',
            'instructions' => 'Use this tool for testing',
            'is_free' => true,
            'category_ids' => [$this->category->id],
            'tags' => ['test', 'calculator'],
        ];

        $response = $this->postJson('/api/v1/tools', $toolData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Tool created successfully',
                'data' => [
                    'name' => 'Test Calculator',
                ],
            ]);

        $this->assertDatabaseHas('tools', [
            'name' => 'Test Calculator',
            'creator_id' => $this->user->id,
        ]);
    }

    /**
     * টুল দেখা যাবে
     */
    public function test_can_view_tool(): void
    {
        $response = $this->getJson("/api/v1/tools/{$this->tool->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'tool',
                    'related_tools',
                    'usage_stats',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'tool' => [
                        'id' => $this->tool->id,
                    ],
                ],
            ]);
    }

    /**
     * টুল আপডেট করা যাবে
     */
    public function test_can_update_tool(): void
    {
        Sanctum::actingAs($this->user);

        $updateData = [
            'name' => 'Updated Calculator',
            'description' => 'Updated description',
            'is_featured' => true,
        ];

        $response = $this->putJson("/api/v1/tools/{$this->tool->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tool updated successfully',
                'data' => [
                    'name' => 'Updated Calculator',
                ],
            ]);

        $this->assertDatabaseHas('tools', [
            'id' => $this->tool->id,
            'name' => 'Updated Calculator',
        ]);
    }

    /**
     * টুল ডিলিট করা যাবে
     */
    public function test_can_delete_tool(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/v1/tools/{$this->tool->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tool deleted successfully',
            ]);

        $this->assertSoftDeleted('tools', [
            'id' => $this->tool->id,
        ]);
    }

    /**
     * টুল সার্চ করা যাবে
     */
    public function test_can_search_tools(): void
    {
        $response = $this->getJson('/api/v1/tools/search?q=calculator');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta',
            ]);
    }

    /**
     * ফিচার্ড টুলস দেখা যাবে
     */
    public function test_can_get_featured_tools(): void
    {
        Tool::factory()->count(5)->create([
            'is_featured' => true,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/tools/featured');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Featured tools retrieved successfully',
            ]);
    }

    /**
     * পপুলার টুলস দেখা যাবে
     */
    public function test_can_get_popular_tools(): void
    {
        Tool::factory()->count(5)->create([
            'uses_count' => 1000,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/tools/popular');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Popular tools retrieved successfully',
            ]);
    }

    /**
     * টুল রেটিং দেওয়া যাবে
     */
    public function test_can_rate_tool(): void
    {
        Sanctum::actingAs($this->user);

        $ratingData = [
            'rating' => 5,
            'review' => 'Excellent tool!',
        ];

        $response = $this->postJson("/api/v1/tools/{$this->tool->id}/rate", $ratingData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tool rated successfully',
            ]);

        $this->assertDatabaseHas('tool_ratings', [
            'tool_id' => $this->tool->id,
            'user_id' => $this->user->id,
            'rating' => 5,
        ]);
    }

    /**
     * টুল ইউসেজ ট্র্যাক করা যাবে
     */
    public function test_can_track_tool_usage(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/tools/{$this->tool->id}/track-usage", [
            'duration' => 60,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tool usage tracked',
            ]);
    }

    /**
     * অননুমোদিত ইউজার টুল তৈরি করতে পারবে না
     */
    public function test_unauthorized_user_cannot_create_tool(): void
    {
        $response = $this->postJson('/api/v1/tools', [
            'name' => 'Unauthorized Tool',
        ]);

        $response->assertStatus(401);
    }

    /**
     * ভুল ডেটা দিয়ে টুল তৈরি করা যাবে না
     */
    public function test_cannot_create_tool_with_invalid_data(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/tools', [
            'name' => '',
            'description' => '',
        ]);

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
     * অস্তিত্বহীন টুল দেখা যাবে না
     */
    public function test_cannot_view_nonexistent_tool(): void
    {
        $response = $this->getJson('/api/v1/tools/nonexistent-slug');

        $response->assertStatus(404);
    }

    /**
     * টুল আইকন আপলোড করা যাবে
     */
    public function test_can_upload_tool_icon(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->user);

        $file = UploadedFile::fake()->image('icon.png', 100, 100);

        $response = $this->postJson('/api/v1/tools', [
            'name' => 'Tool with Icon',
            'description' => 'Tool description',
            'icon' => $file,
            'category_ids' => [$this->category->id],
        ]);

        $response->assertStatus(201);

        Storage::disk('public')->assertExists('tools/icons/' . $file->hashName());
    }

    /**
     * টুল ক্যাটাগরি ফিল্টার করা যাবে
     */
    public function test_can_filter_tools_by_category(): void
    {
        $response = $this->getJson("/api/v1/tools?category_id={$this->category->id}");

        $response->assertStatus(200);
    }

    /**
     * টুল সর্ট করা যাবে
     */
    public function test_can_sort_tools(): void
    {
        $response = $this->getJson('/api/v1/tools?sort_by=name&sort_order=asc');

        $response->assertStatus(200);
    }

    /**
     * টুল পেজিনেশন কাজ করবে
     */
    public function test_tool_pagination_works(): void
    {
        Tool::factory()->count(30)->create([
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/tools?page=2&per_page=10');

        $response->assertStatus(200)
            ->assertJson([
                'pagination' => [
                    'current_page' => 2,
                    'per_page' => 10,
                ],
            ]);
    }
}
