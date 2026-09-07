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

class Tool extends Model
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
        'instructions',
        'icon',
        'thumbnail',
        'screenshot',
        'url',
        'api_url',
        'version',
        'status',
        'is_featured',
        'is_premium',
        'is_free',
        'price',
        'currency',
        'creator_id',
        'category_id',
        'views_count',
        'uses_count',
        'rating',
        'rating_count',
        'average_time_spent',
        'metadata',
        'tags',
        'requirements',
        'compatibility',
        'languages',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'api_url',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_featured' => 'boolean',
        'is_premium' => 'boolean',
        'is_free' => 'boolean',
        'price' => 'decimal:2',
        'views_count' => 'integer',
        'uses_count' => 'integer',
        'rating' => 'float',
        'rating_count' => 'integer',
        'average_time_spent' => 'float',
        'metadata' => 'array',
        'tags' => 'array',
        'requirements' => 'array',
        'compatibility' => 'array',
        'languages' => 'array',
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
        'instructions',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'icon_url',
        'thumbnail_url',
        'screenshot_url',
        'average_rating',
        'is_popular',
        'is_new',
        'is_trending',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'is_featured', 'is_premium', 'price'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tool) {
            // স্লাগ তৈরি
            if (empty($tool->slug)) {
                $tool->slug = static::generateUniqueSlug($tool->name);
            }
            
            // ডিফল্ট স্ট্যাটাস
            if (empty($tool->status)) {
                $tool->status = 'active';
            }
            
            // ডিফল্ট ফ্রি স্ট্যাটাস
            if (!isset($tool->is_free)) {
                $tool->is_free = true;
            }
            
            // ডিফল্ট কারেন্সি
            if (empty($tool->currency)) {
                $tool->currency = 'USD';
            }
            
            // ডিফল্ট ভার্সন
            if (empty($tool->version)) {
                $tool->version = '1.0.0';
            }
        });

        static::created(function ($tool) {
            // ক্যাশ ক্লিয়ার
            Cache::forget('tools_list');
            Cache::forget('featured_tools');
            Cache::forget('popular_tools');
            
            // সার্চ ইনডেক্স
            if ($tool->isActive()) {
                $tool->searchable();
            }
        });

        static::updated(function ($tool) {
            Cache::forget('tools_list');
            Cache::forget('featured_tools');
            Cache::forget('popular_tools');
            Cache::forget("tool_{$tool->id}");
        });

        static::deleted(function ($tool) {
            Cache::forget('tools_list');
            Cache::forget('featured_tools');
            Cache::forget('popular_tools');
            Cache::forget("tool_{$tool->id}");
        });
    }

    /**
     * Get the tool's icon URL.
     */
    public function getIconUrlAttribute(): string
    {
        if ($this->icon) {
            return Storage::url($this->icon);
        }

        return asset('images/default-tool-icon.png');
    }

    /**
     * Get the tool's thumbnail URL.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail) {
            return Storage::url($this->thumbnail);
        }

        return null;
    }

    /**
     * Get the tool's screenshot URL.
     */
    public function getScreenshotUrlAttribute(): ?string
    {
        if ($this->screenshot) {
            return Storage::url($this->screenshot);
        }

        return null;
    }

    /**
     * Get the tool's average rating.
     */
    public function getAverageRatingAttribute(): float
    {
        if ($this->rating_count === 0) {
            return 0.0;
        }

        return round($this->rating / $this->rating_count, 1);
    }

    /**
     * Check if tool is popular.
     */
    public function getIsPopularAttribute(): bool
    {
        return $this->uses_count > 10000;
    }

    /**
     * Check if tool is new.
     */
    public function getIsNewAttribute(): bool
    {
        return $this->created_at && $this->created_at->gt(now()->subDays(30));
    }

    /**
     * Check if tool is trending.
     */
    public function getIsTrendingAttribute(): bool
    {
        return $this->uses_count > 1000 && $this->rating_count > 100;
    }

    /**
     * Get the creator of the tool.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the category of the tool.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'tool_categories')
            ->withTimestamps();
    }

    /**
     * Get the tool's activities.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class);
    }

    /**
     * Get the tool's reviews.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ToolReview::class);
    }

    /**
     * Get the tool's ratings.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(ToolRating::class);
    }

    /**
     * Get the tool's automations.
     */
    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class);
    }

    /**
     * Get the tool's marketplace listings.
     */
    public function marketplaceListings(): HasMany
    {
        return $this->hasMany(MarketplaceListing::class);
    }

    /**
     * Get the tool's API integrations.
     */
    public function apiIntegrations(): HasMany
    {
        return $this->hasMany(ApiIntegration::class);
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
     * Increment views count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
        
        // ক্যাশ আপডেট
        Cache::increment("tool_{$this->id}_views");
    }

    /**
     * Increment uses count.
     */
    public function incrementUses(): void
    {
        $this->increment('uses_count');
        
        // ক্যাশ আপডেট
        Cache::increment("tool_{$this->id}_uses");
    }

    /**
     * Add rating to tool.
     */
    public function addRating(int $rating, int $userId): void
    {
        $this->ratings()->updateOrCreate(
            ['user_id' => $userId],
            ['rating' => $rating]
        );

        $this->update([
            'rating' => $this->ratings()->sum('rating'),
            'rating_count' => $this->ratings()->count(),
        ]);

        Cache::forget("tool_{$this->id}_rating");
    }

    /**
     * Check if tool is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if tool is premium.
     */
    public function isPremium(): bool
    {
        return $this->is_premium;
    }

    /**
     * Check if tool is free.
     */
    public function isFree(): bool
    {
        return $this->is_free && !$this->is_premium;
    }

    /**
     * Get tool's related tools.
     */
    public function getRelatedTools(int $limit = 6)
    {
        return static::whereHas('categories', function ($query) {
            $query->whereIn('categories.id', $this->categories()->pluck('categories.id'));
        })
        ->where('id', '!=', $this->id)
        ->where('status', 'active')
        ->limit($limit)
        ->get();
    }

    /**
     * Get tool's usage statistics.
     */
    public function getUsageStats(): array
    {
        $cacheKey = "tool_{$this->id}_usage_stats";
        
        return Cache::remember($cacheKey, 3600, function () {
            return [
                'total_views' => $this->views_count,
                'total_uses' => $this->uses_count,
                'average_rating' => $this->average_rating,
                'rating_count' => $this->rating_count,
                'average_time_spent' => $this->average_time_spent,
                'daily_uses' => $this->activities()
                    ->where('action', 'tool_used')
                    ->whereDate('created_at', now()->toDateString())
                    ->count(),
                'weekly_uses' => $this->activities()
                    ->where('action', 'tool_used')
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
                'monthly_uses' => $this->activities()
                    ->where('action', 'tool_used')
                    ->whereMonth('created_at', now()->month)
                    ->count(),
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
            'instructions' => $this->instructions,
            'tags' => $this->tags,
            'categories' => $this->categories()->pluck('name')->toArray(),
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'is_premium' => $this->is_premium,
            'rating' => $this->average_rating,
            'uses_count' => $this->uses_count,
        ];
    }

    /**
     * Scope a query to only include active tools.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include featured tools.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include free tools.
     */
    public function scopeFree($query)
    {
        return $query->where('is_free', true)->where('is_premium', false);
    }

    /**
     * Scope a query to only include premium tools.
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    /**
     * Scope a query to only include popular tools.
     */
    public function scopePopular($query)
    {
        return $query->orderBy('uses_count', 'desc');
    }

    /**
     * Scope a query to only include trending tools.
     */
    public function scopeTrending($query)
    {
        return $query->where('uses_count', '>', 1000)
            ->orderBy('rating_count', 'desc');
    }

    /**
     * Scope a query to search tools.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%")
              ->orWhere('description', 'ILIKE', "%{$search}%")
              ->orWhere('tags', 'ILIKE', "%{$search}%");
        });
    }
}
