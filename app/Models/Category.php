<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Translatable\HasTranslations;
use Laravel\Scout\Searchable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasTranslations, Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'parent_id',
        'order',
        'status',
        'is_featured',
        'tools_count',
        'metadata',
        'keywords',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_featured' => 'boolean',
        'order' => 'integer',
        'tools_count' => 'integer',
        'metadata' => 'array',
        'keywords' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'name',
        'description',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'icon_url',
        'children_count',
        'is_parent',
        'is_active',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'is_featured', 'order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            // স্লাগ তৈরি
            if (empty($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->name);
            }
            
            // ডিফল্ট স্ট্যাটাস
            if (empty($category->status)) {
                $category->status = 'active';
            }
            
            // ডিফল্ট অর্ডার
            if (empty($category->order)) {
                $category->order = static::max('order') + 1;
            }
            
            // ডিফল্ট টুলস কাউন্ট
            if (empty($category->tools_count)) {
                $category->tools_count = 0;
            }
            
            // ডিফল্ট কালার
            if (empty($category->color)) {
                $category->color = '#6366f1';
            }
        });

        static::created(function ($category) {
            Cache::forget('categories_list');
            Cache::forget('featured_categories');
            Cache::forget('categories_tree');
        });

        static::updated(function ($category) {
            Cache::forget('categories_list');
            Cache::forget('featured_categories');
            Cache::forget('categories_tree');
            Cache::forget("category_{$category->id}");
        });

        static::deleted(function ($category) {
            Cache::forget('categories_list');
            Cache::forget('featured_categories');
            Cache::forget('categories_tree');
            Cache::forget("category_{$category->id}");
        });
    }

    /**
     * Get the category's icon URL.
     */
    public function getIconUrlAttribute(): string
    {
        if ($this->icon) {
            return Storage::url($this->icon);
        }

        return asset('images/default-category-icon.png');
    }

    /**
     * Get the category's children count.
     */
    public function getChildrenCountAttribute(): int
    {
        return $this->children()->count();
    }

    /**
     * Check if category is parent.
     */
    public function getIsParentAttribute(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if category is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get the tools in this category.
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'tool_categories')
            ->withTimestamps()
            ->withPivot('order');
    }

    /**
     * Get the category's activities.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class);
    }

    /**
     * Generate unique slug.
     */
    protected static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get all tools in this category and its children.
     */
    public function getAllTools()
    {
        $categoryIds = $this->getAllCategoryIds();
        
        return Tool::whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })->where('status', 'active');
    }

    /**
     * Get all category IDs including children.
     */
    public function getAllCategoryIds(): array
    {
        $ids = [$this->id];
        
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getAllCategoryIds());
        }
        
        return $ids;
    }

    /**
     * Update tools count.
     */
    public function updateToolsCount(): void
    {
        $this->update([
            'tools_count' => $this->tools()->count(),
        ]);
        
        Cache::forget("category_{$this->id}_tools_count");
    }

    /**
     * Get category's popular tools.
     */
    public function getPopularTools(int $limit = 10)
    {
        return $this->tools()
            ->where('status', 'active')
            ->orderBy('uses_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get category's latest tools.
     */
    public function getLatestTools(int $limit = 10)
    {
        return $this->tools()
            ->where('status', 'active')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get category's featured tools.
     */
    public function getFeaturedTools(int $limit = 5)
    {
        return $this->tools()
            ->where('status', 'active')
            ->where('is_featured', true)
            ->limit($limit)
            ->get();
    }

    /**
     * Get category's usage statistics.
     */
    public function getUsageStats(): array
    {
        $cacheKey = "category_{$this->id}_usage_stats";
        
        return Cache::remember($cacheKey, 3600, function () {
            $toolIds = $this->tools()->pluck('tools.id');
            
            return [
                'total_tools' => $toolIds->count(),
                'total_uses' => Tool::whereIn('id', $toolIds)->sum('uses_count'),
                'total_views' => Tool::whereIn('id', $toolIds)->sum('views_count'),
                'average_rating' => Tool::whereIn('id', $toolIds)->avg('rating'),
                'premium_tools' => Tool::whereIn('id', $toolIds)->where('is_premium', true)->count(),
                'free_tools' => Tool::whereIn('id', $toolIds)->where('is_free', true)->count(),
            ];
        });
    }

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'tools_count' => $this->tools_count,
        ];
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include featured categories.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include parent categories.
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to only include child categories.
     */
    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope a query to order by tools count.
     */
    public function scopeOrderByToolsCount($query, string $direction = 'desc')
    {
        return $query->orderBy('tools_count', $direction);
    }

    /**
     * Scope a query to search categories.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%")
              ->orWhere('description', 'ILIKE', "%{$search}%")
              ->orWhere('keywords', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Get category tree structure.
     */
    public static function getTree(): array
    {
        $categories = self::active()
            ->orderBy('order')
            ->get();
        
        $tree = [];
        $categoriesById = [];
        
        foreach ($categories as $category) {
            $categoriesById[$category->id] = $category->toArray();
            $categoriesById[$category->id]['children'] = [];
        }
        
        foreach ($categoriesById as $id => &$category) {
            if ($category['parent_id'] && isset($categoriesById[$category['parent_id']])) {
                $categoriesById[$category['parent_id']]['children'][] = &$category;
            } else {
                $tree[] = &$category;
            }
        }
        
        return $tree;
    }

    /**
     * Get all descendant category IDs.
     */
    public function getDescendantIds(): array
    {
        return $this->getAllCategoryIds();
    }

    /**
     * Check if category has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * Check if category has tools.
     */
    public function hasTools(): bool
    {
        return $this->tools()->count() > 0;
    }

    /**
     * Move category to new parent.
     */
    public function moveTo(?int $parentId): void
    {
        $this->update([
            'parent_id' => $parentId,
        ]);
        
        Cache::forget('categories_tree');
    }

    /**
     * Reorder category.
     */
    public function reorder(int $newOrder): void
    {
        $this->update([
            'order' => $newOrder,
        ]);
        
        Cache::forget('categories_list');
    }
}
