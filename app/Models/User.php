<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'bio',
        'address',
        'city',
        'country',
        'postal_code',
        'date_of_birth',
        'gender',
        'language',
        'timezone',
        'currency',
        'status',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
        'last_login_ip',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
        'preferences',
        'metadata',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'date_of_birth' => 'date',
        'two_factor_enabled' => 'boolean',
        'preferences' => 'array',
        'metadata' => 'array',
        'password' => 'hashed',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'avatar_url',
        'full_address',
        'is_online',
        'points_balance',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
        'date_of_birth',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'status', 'last_login_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // ইউজার তৈরি হলে ডিফল্ট সেটিংস
        static::creating(function ($user) {
            if (empty($user->language)) {
                $user->language = config('app.locale', 'bn');
            }
            
            if (empty($user->timezone)) {
                $user->timezone = config('app.timezone', 'Asia/Dhaka');
            }
            
            if (empty($user->currency)) {
                $user->currency = 'USD';
            }
            
            if (empty($user->status)) {
                $user->status = 'active';
            }
            
            if (empty($user->preferences)) {
                $user->preferences = [
                    'theme' => 'light',
                    'notifications' => [
                        'email' => true,
                        'push' => true,
                        'sms' => false,
                    ],
                    'privacy' => [
                        'show_profile' => true,
                        'show_email' => false,
                        'show_phone' => false,
                    ],
                ];
            }
        });

        // ইউজার তৈরি হলে ওয়েলকাম ইমেইল
        static::created(function ($user) {
            $user->sendEmailVerificationNotification();
            $user->createDefaultSettings();
            $user->awardWelcomePoints();
        });
    }

    /**
     * Get the user's avatar URL.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=fff&background=6366f1';
    }

    /**
     * Get the user's full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->country,
            $this->postal_code,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the user's online status.
     */
    public function getIsOnlineAttribute(): bool
    {
        return $this->last_login_at && $this->last_login_at->gt(now()->subMinutes(5));
    }

    /**
     * Get the user's points balance.
     */
    public function getPointsBalanceAttribute(): int
    {
        return $this->pointTransactions()
            ->where('type', 'credit')
            ->sum('points') - $this->pointTransactions()
            ->where('type', 'debit')
            ->sum('points');
    }

    /**
     * Get the user's tools.
     */
    public function tools(): HasMany
    {
        return $this->hasMany(Tool::class, 'creator_id');
    }

    /**
     * Get the user's automations.
     */
    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class);
    }

    /**
     * Get the user's reports.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Get the user's activities.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class);
    }

    /**
     * Get the user's badges.
     */
    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot('earned_at')
            ->withTimestamps();
    }

    /**
     * Get the user's point transactions.
     */
    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    /**
     * Get the user's forum posts.
     */
    public function forumPosts(): HasMany
    {
        return $this->hasMany(ForumPost::class);
    }

    /**
     * Get the user's forum comments.
     */
    public function forumComments(): HasMany
    {
        return $this->hasMany(ForumComment::class);
    }

    /**
     * Get the user's marketplace listings.
     */
    public function marketplaceListings(): HasMany
    {
        return $this->hasMany(MarketplaceListing::class);
    }

    /**
     * Get the user's transactions.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the user's notifications.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the user's API keys.
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Get the user's webhooks.
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    /**
     * Get the user's audit logs.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the user's settings.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(Setting::class);
    }

    /**
     * Get the user's sessions.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    /**
     * Create default settings for the user.
     */
    protected function createDefaultSettings(): void
    {
        $this->settings()->create([
            'theme' => 'light',
            'language' => $this->language ?? 'bn',
            'timezone' => $this->timezone ?? 'Asia/Dhaka',
            'currency' => $this->currency ?? 'USD',
            'notification_preferences' => [
                'email' => true,
                'push' => true,
                'sms' => false,
            ],
        ]);
    }

    /**
     * Award welcome points to the user.
     */
    protected function awardWelcomePoints(): void
    {
        $this->pointTransactions()->create([
            'points' => 100,
            'type' => 'credit',
            'action' => 'welcome_bonus',
            'description' => 'Welcome bonus points',
        ]);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(['admin', 'super-admin']);
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is verified.
     */
    public function isVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Update last login information.
     */
    public function updateLastLogin(): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
    }

    /**
     * Get user's recent tools.
     */
    public function getRecentTools(int $limit = 10)
    {
        return $this->activities()
            ->where('action', 'tool_used')
            ->with('tool')
            ->latest()
            ->take($limit)
            ->get()
            ->pluck('tool')
            ->unique('id');
    }

    /**
     * Get user's favorite tools.
     */
    public function getFavoriteTools()
    {
        return $this->activities()
            ->where('action', 'tool_favorited')
            ->with('tool')
            ->latest()
            ->get()
            ->pluck('tool')
            ->unique('id');
    }

    /**
     * Get user's tool usage statistics.
     */
    public function getToolUsageStats(): array
    {
        $totalToolsUsed = $this->activities()->where('action', 'tool_used')->count();
        $uniqueToolsUsed = $this->activities()->where('action', 'tool_used')->distinct('tool_id')->count();
        $totalTimeSpent = $this->activities()->where('action', 'tool_used')->sum('duration');
        
        return [
            'total_tools_used' => $totalToolsUsed,
            'unique_tools_used' => $uniqueToolsUsed,
            'total_time_spent_minutes' => round($totalTimeSpent / 60, 2),
            'average_time_per_tool' => $totalToolsUsed > 0 ? round($totalTimeSpent / $totalToolsUsed / 60, 2) : 0,
        ];
    }

    /**
     * Get user's achievement progress.
     */
    public function getAchievementProgress(): array
    {
        return [
            'tools_used' => [
                'current' => $this->activities()->where('action', 'tool_used')->count(),
                'target' => 100,
                'percentage' => min(100, round($this->activities()->where('action', 'tool_used')->count() / 100 * 100)),
            ],
            'reviews_written' => [
                'current' => $this->activities()->where('action', 'tool_reviewed')->count(),
                'target' => 50,
                'percentage' => min(100, round($this->activities()->where('action', 'tool_reviewed')->count() / 50 * 100)),
            ],
            'badges_earned' => [
                'current' => $this->badges()->count(),
                'target' => 50,
                'percentage' => min(100, round($this->badges()->count() / 50 * 100)),
            ],
        ];
    }

    /**
     * Generate a new API token.
     */
    public function generateApiToken(string $name = 'default'): string
    {
        return $this->createToken($name)->plainTextToken;
    }

    /**
     * Revoke all API tokens.
     */
    public function revokeAllApiTokens(): void
    {
        $this->tokens()->delete();
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include verified users.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope a query to only include admins.
     */
    public function scopeAdmins($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'super-admin']);
        });
    }

    /**
     * Scope a query to only include online users.
     */
    public function scopeOnline($query)
    {
        return $query->where('last_login_at', '>=', now()->subMinutes(5));
    }
}
