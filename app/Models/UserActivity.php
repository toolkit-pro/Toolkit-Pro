<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class UserActivity extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_activities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'tool_id',
        'action',
        'description',
        'metadata',
        'duration',
        'ip_address',
        'user_agent',
        'device',
        'browser',
        'os',
        'location',
        'session_id',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'duration' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'action_label',
        'duration_formatted',
        'is_recent',
    ];

    /**
     * Activity action types.
     *
     * @var array<string, string>
     */
    public const ACTIONS = [
        'tool_viewed' => 'টুল দেখা হয়েছে',
        'tool_used' => 'টুল ব্যবহার করা হয়েছে',
        'tool_favorited' => 'টুল ফেভারিট করা হয়েছে',
        'tool_rated' => 'টুল রেটিং দেওয়া হয়েছে',
        'tool_reviewed' => 'টুল রিভিউ করা হয়েছে',
        'tool_shared' => 'টুল শেয়ার করা হয়েছে',
        'tool_created' => 'টুল তৈরি করা হয়েছে',
        'tool_updated' => 'টুল আপডেট করা হয়েছে',
        'tool_deleted' => 'টুল ডিলিট করা হয়েছে',
        'search_performed' => 'সার্চ করা হয়েছে',
        'category_viewed' => 'ক্যাটাগরি দেখা হয়েছে',
        'automation_created' => 'অটোমেশন তৈরি করা হয়েছে',
        'automation_run' => 'অটোমেশন চালানো হয়েছে',
        'report_generated' => 'রিপোর্ট তৈরি করা হয়েছে',
        'report_downloaded' => 'রিপোর্ট ডাউনলোড করা হয়েছে',
        'login' => 'লগইন করা হয়েছে',
        'logout' => 'লগআউট করা হয়েছে',
        'profile_updated' => 'প্রোফাইল আপডেট করা হয়েছে',
        'password_changed' => 'পাসওয়ার্ড পরিবর্তন করা হয়েছে',
        'badge_earned' => 'ব্যাজ অর্জিত হয়েছে',
        'points_earned' => 'পয়েন্ট অর্জিত হয়েছে',
        'forum_post_created' => 'ফোরাম পোস্ট তৈরি করা হয়েছে',
        'forum_comment_added' => 'ফোরাম কমেন্ট যোগ করা হয়েছে',
        'marketplace_listing_created' => 'মার্কেটপ্লেস লিস্টিং তৈরি',
        'marketplace_purchase' => 'মার্কেটপ্লেস ক্রয়',
        'transaction_completed' => 'ট্রানজেকশন সম্পন্ন',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($activity) {
            // ক্যাশ ক্লিয়ার
            Cache::forget("user_activities_{$activity->user_id}");
            Cache::forget("user_stats_{$activity->user_id}");
            Cache::forget("recent_activities_{$activity->user_id}");
        });
    }

    /**
     * Get the action label.
     */
    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    /**
     * Get the formatted duration.
     */
    public function getDurationFormattedAttribute(): string
    {
        return format_duration($this->duration ?? 0);
    }

    /**
     * Check if activity is recent.
     */
    public function getIsRecentAttribute(): bool
    {
        return $this->created_at && $this->created_at->gt(now()->subHours(24));
    }

    /**
     * Get the user that owns the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tool associated with the activity.
     */
    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    /**
     * Log a new activity.
     *
     * @param int $userId
     * @param string $action
     * @param array $data
     * @return self
     */
    public static function log(int $userId, string $action, array $data = []): self
    {
        return self::create([
            'user_id' => $userId,
            'tool_id' => $data['tool_id'] ?? null,
            'action' => $action,
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'duration' => $data['duration'] ?? 0,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'device' => $data['device'] ?? null,
            'browser' => $data['browser'] ?? null,
            'os' => $data['os'] ?? null,
            'location' => $data['location'] ?? null,
            'session_id' => $data['session_id'] ?? session()->getId(),
            'status' => $data['status'] ?? 'completed',
        ]);
    }

    /**
     * Get user's recent activities.
     *
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecentForUser(int $userId, int $limit = 20)
    {
        $cacheKey = "recent_activities_{$userId}_limit_{$limit}";

        return Cache::remember($cacheKey, 300, function () use ($userId, $limit) {
            return self::where('user_id', $userId)
                ->with(['tool'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get user's activity statistics.
     *
     * @param int $userId
     * @return array
     */
    public static function getStatsForUser(int $userId): array
    {
        $cacheKey = "user_activity_stats_{$userId}";

        return Cache::remember($cacheKey, 600, function () use ($userId) {
            $activities = self::where('user_id', $userId);

            return [
                'total_activities' => $activities->count(),
                'total_tools_used' => (clone $activities)->where('action', 'tool_used')->count(),
                'total_tools_viewed' => (clone $activities)->where('action', 'tool_viewed')->count(),
                'total_tools_favorited' => (clone $activities)->where('action', 'tool_favorited')->count(),
                'total_searches' => (clone $activities)->where('action', 'search_performed')->count(),
                'total_automations' => (clone $activities)->where('action', 'automation_run')->count(),
                'total_reports' => (clone $activities)->where('action', 'report_generated')->count(),
                'total_logins' => (clone $activities)->where('action', 'login')->count(),
                'average_duration' => round($activities->avg('duration') ?? 0),
                'last_activity' => $activities->max('created_at'),
            ];
        });
    }

    /**
     * Get activity trends for user.
     *
     * @param int $userId
     * @param int $days
     * @return array
     */
    public static function getTrendsForUser(int $userId, int $days = 30): array
    {
        $cacheKey = "user_activity_trends_{$userId}_{$days}";

        return Cache::remember($cacheKey, 3600, function () use ($userId, $days) {
            $startDate = now()->subDays($days)->startOfDay();

            return self::where('user_id', $userId)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->date => $item->count];
                })
                ->toArray();
        });
    }

    /**
     * Get popular actions.
     *
     * @param int $limit
     * @return array
     */
    public static function getPopularActions(int $limit = 10): array
    {
        $cacheKey = "popular_actions_{$limit}";

        return Cache::remember($cacheKey, 3600, function () use ($limit) {
            return self::select('action')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'action' => $item->action,
                        'label' => self::ACTIONS[$item->action] ?? $item->action,
                        'count' => $item->count,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Scope a query to only include activities of a specific type.
     */
    public function scopeOfType(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include recent activities.
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope a query to only include completed activities.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include successful activities.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->whereIn('status', ['completed', 'success']);
    }
}
