<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\API\v1\AuthController;
use App\Http\Controllers\API\v1\UserController;
use App\Http\Controllers\API\v1\ToolController;
use App\Http\Controllers\API\v1\CategoryController;
use App\Http\Controllers\API\v1\SearchController;
use App\Http\Controllers\API\v1\AutomationController;
use App\Http\Controllers\API\v1\AnalyticsController;
use App\Http\Controllers\API\v1\ReportController;
use App\Http\Controllers\API\v1\GamificationController;
use App\Http\Controllers\API\v1\SocialController;
use App\Http\Controllers\API\v1\MarketplaceController;
use App\Http\Controllers\API\v1\BlockchainController;
use App\Http\Controllers\API\v1\AIController;
use App\Http\Controllers\API\v1\CollaborationController;
use App\Http\Controllers\API\v1\NotificationController;
use App\Http\Controllers\API\v1\SettingsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
| Toolkit Pro v2.0 - বিশ্বের সবচেয়ে সম্পূর্ণ অনলাইন টুলস প্ল্যাটফর্ম
|
*/

// ===================
// API Version Prefix
// ===================
Route::prefix('v1')->group(function () {

    // ===================
    // Public Routes
    // ===================
    Route::prefix('auth')->group(function () {
        // অথেনটিকেশন
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:10,1');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:3,1');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:3,1');
        Route::post('/verify-2fa', [AuthController::class, 'verifyTwoFactor']);
        Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->name('verification.verify');
    });

    // পাবলিক টুলস
    Route::prefix('tools')->group(function () {
        Route::get('/', [ToolController::class, 'index']);
        Route::get('/featured', [ToolController::class, 'featured']);
        Route::get('/popular', [ToolController::class, 'popular']);
        Route::get('/trending', [ToolController::class, 'trending']);
        Route::get('/newest', [ToolController::class, 'newest']);
        Route::get('/search', [ToolController::class, 'search']);
        Route::get('/semantic-search', [ToolController::class, 'semanticSearch']);
        Route::get('/suggestions', [ToolController::class, 'suggestions']);
        Route::get('/{slug}', [ToolController::class, 'show']);
    });

    // পাবলিক ক্যাটাগরি
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/tree', [CategoryController::class, 'tree']);
        Route::get('/featured', [CategoryController::class, 'featured']);
        Route::get('/{slug}', [CategoryController::class, 'show']);
        Route::get('/{slug}/tools', [CategoryController::class, 'tools']);
    });

    // ===================
    // Protected Routes
    // ===================
    Route::middleware(['auth:sanctum', 'verified'])->group(function () {

        // অথেনটিকেটেড ইউজার
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAllDevices']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
            Route::post('/resend-verification', [AuthController::class, 'resendVerificationEmail']);
        });

        // ইউজার ম্যানেজমেন্ট
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])
                ->middleware('permission:view-users');
            Route::get('/profile', [UserController::class, 'profile']);
            Route::put('/profile', [UserController::class, 'updateProfile']);
            Route::put('/password', [UserController::class, 'updatePassword']);
            Route::put('/preferences', [UserController::class, 'updatePreferences']);
            Route::get('/stats', [UserController::class, 'stats']);
            Route::get('/activities', [UserController::class, 'activities']);
            Route::get('/{id}', [UserController::class, 'show'])
                ->middleware('permission:view-users');
            Route::put('/{id}', [UserController::class, 'update'])
                ->middleware('permission:edit-users');
            Route::delete('/{id}', [UserController::class, 'destroy'])
                ->middleware('permission:delete-users');
        });

        // টুল CRUD
        Route::prefix('tools')->group(function () {
            Route::post('/', [ToolController::class, 'store'])
                ->middleware('permission:create-tools');
            Route::put('/{id}', [ToolController::class, 'update'])
                ->middleware('permission:edit-tools');
            Route::delete('/{id}', [ToolController::class, 'destroy'])
                ->middleware('permission:delete-tools');
            Route::post('/{id}/rate', [ToolController::class, 'rate']);
            Route::post('/{id}/track-usage', [ToolController::class, 'trackUsage']);
        });

        // ক্যাটাগরি CRUD
        Route::prefix('categories')->group(function () {
            Route::post('/', [CategoryController::class, 'store'])
                ->middleware('permission:create-categories');
            Route::put('/{id}', [CategoryController::class, 'update'])
                ->middleware('permission:edit-categories');
            Route::delete('/{id}', [CategoryController::class, 'destroy'])
                ->middleware('permission:delete-categories');
        });

        // সার্চ
        Route::prefix('search')->group(function () {
            Route::get('/', [SearchController::class, 'search']);
            Route::get('/advanced', [SearchController::class, 'advancedSearch']);
            Route::get('/voice', [SearchController::class, 'voiceSearch']);
            Route::get('/image', [SearchController::class, 'imageSearch']);
            Route::get('/history', [SearchController::class, 'history']);
            Route::delete('/history/{id}', [SearchController::class, 'deleteHistory']);
        });

        // অটোমেশন
        Route::prefix('automations')->group(function () {
            Route::get('/', [AutomationController::class, 'index']);
            Route::post('/', [AutomationController::class, 'store']);
            Route::get('/templates', [AutomationController::class, 'templates']);
            Route::get('/{id}', [AutomationController::class, 'show']);
            Route::put('/{id}', [AutomationController::class, 'update']);
            Route::delete('/{id}', [AutomationController::class, 'destroy']);
            Route::post('/{id}/run', [AutomationController::class, 'run']);
            Route::post('/{id}/toggle', [AutomationController::class, 'toggle']);
            Route::get('/{id}/logs', [AutomationController::class, 'logs']);
        });

        // অ্যানালিটিক্স
        Route::prefix('analytics')->group(function () {
            Route::get('/usage', [AnalyticsController::class, 'usage']);
            Route::get('/performance', [AnalyticsController::class, 'performance']);
            Route::get('/revenue', [AnalyticsController::class, 'revenue']);
            Route::get('/engagement', [AnalyticsController::class, 'engagement']);
            Route::get('/growth', [AnalyticsController::class, 'growth']);
            Route::get('/custom', [AnalyticsController::class, 'custom']);
        });

        // রিপোর্ট
        Route::prefix('reports')->group(function () {
            Route::get('/', [ReportController::class, 'index']);
            Route::post('/', [ReportController::class, 'store']);
            Route::get('/types', [ReportController::class, 'types']);
            Route::get('/{id}', [ReportController::class, 'show']);
            Route::get('/{id}/download', [ReportController::class, 'download']);
            Route::delete('/{id}', [ReportController::class, 'destroy']);
            Route::post('/{id}/schedule', [ReportController::class, 'schedule']);
        });

        // গেমিফিকেশন
        Route::prefix('gamification')->group(function () {
            Route::get('/points', [GamificationController::class, 'points']);
            Route::get('/badges', [GamificationController::class, 'badges']);
            Route::get('/achievements', [GamificationController::class, 'achievements']);
            Route::get('/leaderboard', [GamificationController::class, 'leaderboard']);
            Route::get('/level', [GamificationController::class, 'level']);
        });

        // সোশ্যাল
        Route::prefix('social')->group(function () {
            // ফোরাম
            Route::get('/forum/posts', [SocialController::class, 'forumPosts']);
            Route::post('/forum/posts', [SocialController::class, 'createForumPost']);
            Route::get('/forum/posts/{id}', [SocialController::class, 'showForumPost']);
            Route::post('/forum/posts/{id}/comments', [SocialController::class, 'addForumComment']);
            Route::post('/forum/posts/{id}/like', [SocialController::class, 'likeForumPost']);
            
            // মেসেজিং
            Route::get('/messages', [SocialController::class, 'messages']);
            Route::post('/messages', [SocialController::class, 'sendMessage']);
            Route::get('/messages/{userId}', [SocialController::class, 'conversation']);
        });

        // মার্কেটপ্লেস
        Route::prefix('marketplace')->group(function () {
            Route::get('/listings', [MarketplaceController::class, 'index']);
            Route::post('/listings', [MarketplaceController::class, 'store']);
            Route::get('/listings/{id}', [MarketplaceController::class, 'show']);
            Route::put('/listings/{id}', [MarketplaceController::class, 'update']);
            Route::delete('/listings/{id}', [MarketplaceController::class, 'destroy']);
            Route::post('/purchase/{id}', [MarketplaceController::class, 'purchase']);
            Route::get('/transactions', [MarketplaceController::class, 'transactions']);
        });

        // ব্লকচেইন
        Route::prefix('blockchain')->group(function () {
            Route::get('/wallet', [BlockchainController::class, 'wallet']);
            Route::post('/wallet/connect', [BlockchainController::class, 'connectWallet']);
            Route::post('/transactions', [BlockchainController::class, 'createTransaction']);
            Route::get('/transactions', [BlockchainController::class, 'transactions']);
            Route::post('/contracts', [BlockchainController::class, 'deployContract']);
            Route::post('/nft/mint', [BlockchainController::class, 'mintNFT']);
        });

        // AI
        Route::prefix('ai')->group(function () {
            Route::post('/generate/text', [AIController::class, 'generateText']);
            Route::post('/generate/image', [AIController::class, 'generateImage']);
            Route::post('/generate/video', [AIController::class, 'generateVideo']);
            Route::post('/generate/audio', [AIController::class, 'generateAudio']);
            Route::post('/generate/code', [AIController::class, 'generateCode']);
            Route::post('/analyze/sentiment', [AIController::class, 'analyzeSentiment']);
            Route::post('/predict', [AIController::class, 'predict']);
        });

        // কোলাবোরেশন
        Route::prefix('collaboration')->group(function () {
            Route::post('/sessions', [CollaborationController::class, 'startSession']);
            Route::get('/sessions', [CollaborationController::class, 'sessions']);
            Route::get('/sessions/{id}', [CollaborationController::class, 'session']);
            Route::post('/sessions/{id}/join', [CollaborationController::class, 'joinSession']);
            Route::post('/sessions/{id}/leave', [CollaborationController::class, 'leaveSession']);
            Route::post('/sessions/{id}/message', [CollaborationController::class, 'sendMessage']);
            Route::post('/sessions/{id}/share', [CollaborationController::class, 'shareFile']);
        });

        // নোটিফিকেশন
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread', [NotificationController::class, 'unread']);
            Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
            Route::delete('/clear-all', [NotificationController::class, 'clearAll']);
        });

        // সেটিংস
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingsController::class, 'index']);
            Route::put('/', [SettingsController::class, 'update']);
            Route::put('/notifications', [SettingsController::class, 'updateNotifications']);
            Route::put('/privacy', [SettingsController::class, 'updatePrivacy']);
            Route::put('/security', [SettingsController::class, 'updateSecurity']);
            Route::get('/api-keys', [SettingsController::class, 'apiKeys']);
            Route::post('/api-keys', [SettingsController::class, 'createApiKey']);
            Route::delete('/api-keys/{id}', [SettingsController::class, 'deleteApiKey']);
        });
    });

    // ===================
    // Admin Routes
    // ===================
    Route::middleware(['auth:sanctum', 'verified', 'role:admin,super-admin'])
        ->prefix('admin')
        ->group(function () {
            
            // ড্যাশবোর্ড
            Route::get('/dashboard', [AnalyticsController::class, 'dashboard']);
            
            // ইউজার ম্যানেজমেন্ট
            Route::get('/users', [UserController::class, 'adminIndex']);
            Route::put('/users/{id}/status', [UserController::class, 'updateStatus']);
            Route::put('/users/{id}/role', [UserController::class, 'updateRole']);
            
            // টুল ম্যানেজমেন্ট
            Route::get('/tools', [ToolController::class, 'adminIndex']);
            Route::put('/tools/{id}/approve', [ToolController::class, 'approve']);
            Route::put('/tools/{id}/reject', [ToolController::class, 'reject']);
            
            // ক্যাটাগরি ম্যানেজমেন্ট
            Route::get('/categories', [CategoryController::class, 'adminIndex']);
            
            // সিস্টেম সেটিংস
            Route::get('/settings', [SettingsController::class, 'adminSettings']);
            Route::put('/settings', [SettingsController::class, 'updateAdminSettings']);
            
            // অডিট লগ
            Route::get('/audit-logs', [AnalyticsController::class, 'auditLogs']);
        });
});

// ===================
// WebSocket Broadcasting
// ===================
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// ===================
// Fallback Route
// ===================
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'error' => [
            'code' => 'NOT_FOUND',
            'status_code' => 404,
        ],
    ], 404);
});
