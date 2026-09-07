<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use App\Models\Category;
use App\Http\Requests\StoreToolRequest;
use App\Http\Requests\UpdateToolRequest;
use App\Http\Resources\ToolResource;
use App\Services\SmartToolSearch;
use App\Services\IntelligentToolEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ToolController extends Controller
{
    protected $smartSearch;
    protected $intelligentEngine;

    public function __construct(
        SmartToolSearch $smartSearch,
        IntelligentToolEngine $intelligentEngine
    ) {
        $this->smartSearch = $smartSearch;
        $this->intelligentEngine = $intelligentEngine;
    }

    /**
     * Display a listing of tools.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $paginationParams = $this->getPaginationParams($request);
        $searchParams = $this->getSearchParams($request);

        $cacheKey = 'tools_list_' . md5(json_encode([
            'page' => $paginationParams['page'],
            'per_page' => $paginationParams['per_page'],
            'sort_by' => $paginationParams['sort_by'],
            'sort_order' => $paginationParams['sort_order'],
            'search' => $searchParams['search'],
            'filters' => $searchParams['filters'],
        ]));

        $tools = $this->cachedResponse($cacheKey, function () use ($request, $paginationParams, $searchParams) {
            $query = Tool::query()->with(['categories', 'creator']);

            // ফিল্টারিং
            if (!empty($searchParams['search'])) {
                $query->search($searchParams['search']);
            }

            // ক্যাটাগরি ফিল্টার
            if ($request->has('category_id')) {
                $query->whereHas('categories', function ($q) use ($request) {
                    $q->where('categories.id', $request->category_id);
                });
            }

            // স্ট্যাটাস ফিল্টার
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // প্রাইস ফিল্টার
            if ($request->has('is_free')) {
                $query->where('is_free', $request->boolean('is_free'));
            }
            if ($request->has('is_premium')) {
                $query->where('is_premium', $request->boolean('is_premium'));
            }
            if ($request->has('is_featured')) {
                $query->where('is_featured', $request->boolean('is_featured'));
            }

            // রেটিং ফিল্টার
            if ($request->has('min_rating')) {
                $query->where('rating', '>=', $request->min_rating);
            }

            // সর্টিং
            switch ($paginationParams['sort_by']) {
                case 'name':
                    $query->orderBy('name', $paginationParams['sort_order']);
                    break;
                case 'rating':
                    $query->orderBy('rating', $paginationParams['sort_order']);
                    break;
                case 'views':
                    $query->orderBy('views_count', $paginationParams['sort_order']);
                    break;
                case 'uses':
                    $query->orderBy('uses_count', $paginationParams['sort_order']);
                    break;
                case 'price':
                    $query->orderBy('price', $paginationParams['sort_order']);
                    break;
                default:
                    $query->orderBy('created_at', $paginationParams['sort_order']);
            }

            return $query->paginate($paginationParams['per_page']);
        }, 300); // ৫ মিনিট ক্যাশ

        return $this->paginatedResponse(
            $tools,
            'Tools retrieved successfully',
            ['total_categories' => Category::count()]
        );
    }

    /**
     * Store a newly created tool.
     *
     * @param StoreToolRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreToolRequest $request)
    {
        $this->authorizeAction('create', Tool::class);

        try {
            DB::beginTransaction();

            $validated = $request->validated();
            
            // আইকন আপলোড
            if ($request->hasFile('icon')) {
                $validated['icon'] = $request->file('icon')->store('tools/icons', 'public');
            }

            // থাম্বনেইল আপলোড
            if ($request->hasFile('thumbnail')) {
                $validated['thumbnail'] = $request->file('thumbnail')->store('tools/thumbnails', 'public');
            }

            // স্ক্রিনশট আপলোড
            if ($request->hasFile('screenshot')) {
                $validated['screenshot'] = $request->file('screenshot')->store('tools/screenshots', 'public');
            }

            // ক্রিয়েটর সেট
            $validated['creator_id'] = $this->getUserId();

            $tool = Tool::create($validated);

            // ক্যাটাগরি অ্যাটাচ
            if ($request->has('category_ids')) {
                $tool->categories()->sync($request->category_ids);
            }

            // অ্যাক্টিভিটি লগ
            activity()
                ->performedOn($tool)
                ->causedBy(auth()->user())
                ->withProperties($validated)
                ->log('tool_created');

            // পয়েন্ট অ্যাওয়ার্ড
            app('GamificationEngine')->awardPoints(auth()->id(), 'tool_created');

            DB::commit();

            // ক্যাশ ক্লিয়ার
            $this->clearCache('tools');

            return $this->createdResponse(
                new ToolResource($tool),
                'Tool created successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tool creation failed: ' . $e->getMessage());
            
            return $this->serverErrorResponse('Failed to create tool');
        }
    }

    /**
     * Display the specified tool.
     *
     * @param string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $slug)
    {
        $cacheKey = "tool_show_{$slug}";

        $tool = $this->cachedResponse($cacheKey, function () use ($slug) {
            return Tool::where('slug', $slug)
                ->with(['categories', 'creator', 'reviews.user', 'ratings'])
                ->firstOrFail();
        }, 3600);

        // ভিউ কাউন্ট বাড়ান
        $tool->incrementViews();

        // রিলেটেড টুলস
        $relatedTools = $tool->getRelatedTools(6);

        // ইউসেজ স্ট্যাটস
        $usageStats = $tool->getUsageStats();

        // ইউজার অ্যাক্টিভিটি ট্র্যাক
        if (auth()->check()) {
            activity()
                ->performedOn($tool)
                ->causedBy(auth()->user())
                ->withProperties(['action' => 'tool_viewed'])
                ->log('tool_viewed');
        }

        return $this->successResponse([
            'tool' => new ToolResource($tool),
            'related_tools' => ToolResource::collection($relatedTools),
            'usage_stats' => $usageStats,
        ], 'Tool retrieved successfully');
    }

    /**
     * Update the specified tool.
     *
     * @param UpdateToolRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateToolRequest $request, int $id)
    {
        $tool = Tool::findOrFail($id);
        $this->authorizeAction('update', $tool);

        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // ফাইল আপডেট
            if ($request->hasFile('icon')) {
                // পুরনো ফাইল ডিলিট
                if ($tool->icon) {
                    Storage::disk('public')->delete($tool->icon);
                }
                $validated['icon'] = $request->file('icon')->store('tools/icons', 'public');
            }

            if ($request->hasFile('thumbnail')) {
                if ($tool->thumbnail) {
                    Storage::disk('public')->delete($tool->thumbnail);
                }
                $validated['thumbnail'] = $request->file('thumbnail')->store('tools/thumbnails', 'public');
            }

            if ($request->hasFile('screenshot')) {
                if ($tool->screenshot) {
                    Storage::disk('public')->delete($tool->screenshot);
                }
                $validated['screenshot'] = $request->file('screenshot')->store('tools/screenshots', 'public');
            }

            $tool->update($validated);

            // ক্যাটাগরি সিঙ্ক
            if ($request->has('category_ids')) {
                $tool->categories()->sync($request->category_ids);
            }

            activity()
                ->performedOn($tool)
                ->causedBy(auth()->user())
                ->withProperties($validated)
                ->log('tool_updated');

            DB::commit();

            $this->clearCache('tools');
            Cache::forget("tool_show_{$tool->slug}");

            return $this->successResponse(
                new ToolResource($tool),
                'Tool updated successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tool update failed: ' . $e->getMessage());
            
            return $this->serverErrorResponse('Failed to update tool');
        }
    }

    /**
     * Remove the specified tool.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $tool = Tool::findOrFail($id);
        $this->authorizeAction('delete', $tool);

        try {
            DB::beginTransaction();

            // ফাইল ডিলিট
            if ($tool->icon) {
                Storage::disk('public')->delete($tool->icon);
            }
            if ($tool->thumbnail) {
                Storage::disk('public')->delete($tool->thumbnail);
            }
            if ($tool->screenshot) {
                Storage::disk('public')->delete($tool->screenshot);
            }

            // ক্যাটাগরি ডিটাচ
            $tool->categories()->detach();

            activity()
                ->performedOn($tool)
                ->causedBy(auth()->user())
                ->log('tool_deleted');

            $tool->delete();

            DB::commit();

            $this->clearCache('tools');

            return $this->successResponse(null, 'Tool deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tool deletion failed: ' . $e->getMessage());
            
            return $this->serverErrorResponse('Failed to delete tool');
        }
    }

    /**
     * Search tools.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        
        if (empty($query)) {
            return $this->validationErrorResponse(['q' => ['Search query is required']]);
        }

        $results = $this->smartSearch->fuzzySearch($query, Tool::active()->get());

        return $this->successResponse(
            ToolResource::collection(collect($results)->pluck('tool')),
            'Search results retrieved successfully',
            ['total' => count($results)]
        );
    }

    /**
     * Semantic search tools.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function semanticSearch(Request $request)
    {
        $query = $request->input('query', '');
        
        if (empty($query)) {
            return $this->validationErrorResponse(['query' => ['Search query is required']]);
        }

        $results = $this->smartSearch->semanticSearch($query);

        return $this->successResponse(
            ToolResource::collection(collect($results)),
            'Semantic search results retrieved successfully'
        );
    }

    /**
     * Get featured tools.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function featured()
    {
        $tools = $this->cachedResponse('featured_tools', function () {
            return Tool::active()
                ->featured()
                ->with(['categories'])
                ->orderBy('rating', 'desc')
                ->limit(20)
                ->get();
        }, 3600);

        return $this->successResponse(
            ToolResource::collection($tools),
            'Featured tools retrieved successfully'
        );
    }

    /**
     * Get popular tools.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function popular()
    {
        $tools = $this->cachedResponse('popular_tools', function () {
            return Tool::active()
                ->popular()
                ->with(['categories'])
                ->limit(20)
                ->get();
        }, 3600);

        return $this->successResponse(
            ToolResource::collection($tools),
            'Popular tools retrieved successfully'
        );
    }

    /**
     * Get trending tools.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function trending()
    {
        $tools = $this->cachedResponse('trending_tools', function () {
            return Tool::active()
                ->trending()
                ->with(['categories'])
                ->limit(20)
                ->get();
        }, 1800);

        return $this->successResponse(
            ToolResource::collection($tools),
            'Trending tools retrieved successfully'
        );
    }

    /**
     * Get new tools.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function newest()
    {
        $tools = $this->cachedResponse('newest_tools', function () {
            return Tool::active()
                ->with(['categories'])
                ->latest()
                ->limit(20)
                ->get();
        }, 1800);

        return $this->successResponse(
            ToolResource::collection($tools),
            'Newest tools retrieved successfully'
        );
    }

    /**
     * Get tool suggestions.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function suggestions(Request $request)
    {
        $userId = $this->getUserId();
        $context = $request->all();

        $suggestions = $this->intelligentEngine->suggestTools($userId, $context);

        return $this->successResponse(
            ToolResource::collection(collect($suggestions)),
            'Tool suggestions retrieved successfully'
        );
    }

    /**
     * Rate a tool.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function rate(Request $request, int $id)
    {
        $validated = $this->validateRequest($request, [
            'rating' => ['required', 'integer', 'between:1,5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ]);

        $tool = Tool::findOrFail($id);

        $tool->addRating($validated['rating'], $this->getUserId());

        if (!empty($validated['review'])) {
            $tool->reviews()->create([
                'user_id' => $this->getUserId(),
                'review' => $validated['review'],
            ]);
        }

        // পয়েন্ট অ্যাওয়ার্ড
        app('GamificationEngine')->awardPoints($this->getUserId(), 'tool_rating');

        return $this->successResponse(
            ['average_rating' => $tool->average_rating],
            'Tool rated successfully'
        );
    }

    /**
     * Track tool usage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function trackUsage(Request $request, int $id)
    {
        $tool = Tool::findOrFail($id);

        $tool->incrementUses();

        // ইউজার অ্যাক্টিভিটি
        if (auth()->check()) {
            activity()
                ->performedOn($tool)
                ->causedBy(auth()->user())
                ->withProperties([
                    'duration' => $request->input('duration', 0),
                ])
                ->log('tool_used');

            // পয়েন্ট অ্যাওয়ার্ড
            app('GamificationEngine')->awardPoints($this->getUserId(), 'tool_use');
        }

        return $this->successResponse(null, 'Tool usage tracked');
    }
}
