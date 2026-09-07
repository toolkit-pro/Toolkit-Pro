<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ToolController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\API\v1\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
| Toolkit Pro v2.0 - বিশ্বের সবচেয়ে সম্পূর্ণ অনলাইন টুলস প্ল্যাটফর্ম
|
*/

// ===================
// Public Routes
// ===================

// হোম পেজ
Route::get('/', [PageController::class, 'home'])->name('home');

// স্ট্যাটিক পেজ
Route::prefix('pages')->group(function () {
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('privacy');
    Route::get('/terms-of-service', [PageController::class, 'termsOfService'])->name('terms');
    Route::get('/faq', [PageController::class, 'faq'])->name('faq');
    Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
    Route::get('/api-documentation', [PageController::class, 'apiDocs'])->name('api.docs');
    Route::get('/changelog', [PageController::class, 'changelog'])->name('changelog');
    Route::get('/roadmap', [PageController::class, 'roadmap'])->name('roadmap');
    Route::get('/careers', [PageController::class, 'careers'])->name('careers');
    Route::get('/press', [PageController::class, 'press'])->name('press');
    Route::get('/partners', [PageController::class, 'partners'])->name('partners');
});

// টুলস পাবলিক পেজ
Route::prefix('tools')->group(function () {
    Route::get('/', [ToolController::class, 'index'])->name('tools.index');
    Route::get('/search', [ToolController::class, 'search'])->name('tools.search');
    Route::get('/category/{slug}', [ToolController::class, 'byCategory'])->name('tools.category');
    Route::get('/{slug}', [ToolController::class, 'show'])->name('tools.show');
    Route::get('/{slug}/embed', [ToolController::class, 'embed'])->name('tools.embed');
});

// ক্যাটাগরি পাবলিক পেজ
Route::prefix('categories')->group(function () {
    Route::get('/', [PageController::class, 'categories'])->name('categories.index');
    Route::get('/{slug}', [PageController::class, 'category'])->name('categories.show');
});

// ব্লগ
Route::prefix('blog')->group(function () {
    Route::get('/', [PageController::class, 'blogIndex'])->name('blog.index');
    Route::get('/{slug}', [PageController::class, 'blogShow'])->name('blog.show');
    Route::get('/category/{category}', [PageController::class, 'blogCategory'])->name('blog.category');
    Route::get('/tag/{tag}', [PageController::class, 'blogTag'])->name('blog.tag');
});

// কন্টাক্ট ফর্ম
Route::post('/contact', [PageController::class, 'sendContactForm'])->name('contact.send');

// নিউজলেটার
Route::post('/newsletter/subscribe', [PageController::class, 'newsletterSubscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [PageController::class, 'newsletterUnsubscribe'])->name('newsletter.unsubscribe');

// ===================
// Authentication Routes
// ===================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Social Login
Route::prefix('auth')->group(function () {
    Route::get('/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);
    Route::get('/github', [AuthController::class, 'redirectToGithub'])->name('auth.github');
    Route::get('/github/callback', [AuthController::class, 'handleGithubCallback']);
    Route::get('/facebook', [AuthController::class, 'redirectToFacebook'])->name('auth.facebook');
    Route::get('/facebook/callback', [AuthController::class, 'handleFacebookCallback']);
    Route::get('/twitter', [AuthController::class, 'redirectToTwitter'])->name('auth.twitter');
    Route::get('/twitter/callback', [AuthController::class, 'handleTwitterCallback']);
});

// Email Verification
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [AuthController::class, 'showVerificationNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
    Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail'])->name('verification.resend');
});

// ===================
// Protected Routes
// ===================
Route::middleware(['auth', 'verified'])->group(function () {

    // ড্যাশবোর্ড
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [DashboardController::class, 'analytics'])->name('dashboard.analytics');
        Route::get('/reports', [DashboardController::class, 'reports'])->name('dashboard.reports');
        Route::get('/automations', [DashboardController::class, 'automations'])->name('dashboard.automations');
        Route::get('/favorites', [DashboardController::class, 'favorites'])->name('dashboard.favorites');
        Route::get('/history', [DashboardController::class, 'history'])->name('dashboard.history');
        Route::get('/notifications', [DashboardController::class, 'notifications'])->name('dashboard.notifications');
        Route::get('/messages', [DashboardController::class, 'messages'])->name('dashboard.messages');
        Route::get('/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
        Route::get('/billing', [DashboardController::class, 'billing'])->name('dashboard.billing');
        Route::get('/api-keys', [DashboardController::class, 'apiKeys'])->name('dashboard.api-keys');
    });

    // ইউজার টুলস
    Route::prefix('my-tools')->group(function () {
        Route::get('/', [ToolController::class, 'myTools'])->name('my-tools.index');
        Route::get('/create', [ToolController::class, 'create'])->name('my-tools.create');
        Route::post('/', [ToolController::class, 'store'])->name('my-tools.store');
        Route::get('/{id}/edit', [ToolController::class, 'edit'])->name('my-tools.edit');
        Route::put('/{id}', [ToolController::class, 'update'])->name('my-tools.update');
        Route::delete('/{id}', [ToolController::class, 'destroy'])->name('my-tools.destroy');
    });

    // অটোমেশন
    Route::prefix('automations')->group(function () {
        Route::get('/', [DashboardController::class, 'automations'])->name('automations.index');
        Route::post('/', [DashboardController::class, 'createAutomation'])->name('automations.store');
        Route::put('/{id}', [DashboardController::class, 'updateAutomation'])->name('automations.update');
        Route::delete('/{id}', [DashboardController::class, 'deleteAutomation'])->name('automations.destroy');
        Route::post('/{id}/run', [DashboardController::class, 'runAutomation'])->name('automations.run');
    });

    // মার্কেটপ্লেস
    Route::prefix('marketplace')->group(function () {
        Route::get('/', [DashboardController::class, 'marketplace'])->name('marketplace.index');
        Route::get('/sell', [DashboardController::class, 'sellTool'])->name('marketplace.sell');
        Route::post('/listings', [DashboardController::class, 'createListing'])->name('marketplace.listings.store');
        Route::get('/purchases', [DashboardController::class, 'purchases'])->name('marketplace.purchases');
    });

    // গেমিফিকেশন
    Route::prefix('gamification')->group(function () {
        Route::get('/leaderboard', [DashboardController::class, 'leaderboard'])->name('gamification.leaderboard');
        Route::get('/badges', [DashboardController::class, 'badges'])->name('gamification.badges');
        Route::get('/achievements', [DashboardController::class, 'achievements'])->name('gamification.achievements');
    });

    // সোশ্যাল
    Route::prefix('community')->group(function () {
        Route::get('/forum', [DashboardController::class, 'forum'])->name('community.forum');
        Route::post('/forum/posts', [DashboardController::class, 'createForumPost'])->name('community.forum.posts.store');
        Route::get('/forum/posts/{id}', [DashboardController::class, 'showForumPost'])->name('community.forum.posts.show');
        Route::post('/forum/posts/{id}/comments', [DashboardController::class, 'addForumComment'])->name('community.forum.comments.store');
    });

    // লগআউট
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// ===================
// Admin Routes
// ===================
Route::middleware(['auth', 'verified', 'role:admin,super-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // অ্যাডমিন ড্যাশবোর্ড
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');

        // ইউজার ম্যানেজমেন্ট
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminController::class, 'users'])->name('users.index');
            Route::get('/{id}', [AdminController::class, 'showUser'])->name('users.show');
            Route::put('/{id}', [AdminController::class, 'updateUser'])->name('users.update');
            Route::put('/{id}/status', [AdminController::class, 'updateUserStatus'])->name('users.status');
            Route::put('/{id}/role', [AdminController::class, 'updateUserRole'])->name('users.role');
            Route::delete('/{id}', [AdminController::class, 'deleteUser'])->name('users.destroy');
        });

        // টুল ম্যানেজমেন্ট
        Route::prefix('tools')->group(function () {
            Route::get('/', [AdminController::class, 'tools'])->name('tools.index');
            Route::get('/{id}', [AdminController::class, 'showTool'])->name('tools.show');
            Route::put('/{id}', [AdminController::class, 'updateTool'])->name('tools.update');
            Route::put('/{id}/approve', [AdminController::class, 'approveTool'])->name('tools.approve');
            Route::put('/{id}/reject', [AdminController::class, 'rejectTool'])->name('tools.reject');
            Route::delete('/{id}', [AdminController::class, 'deleteTool'])->name('tools.destroy');
        });

        // ক্যাটাগরি ম্যানেজমেন্ট
        Route::prefix('categories')->group(function () {
            Route::get('/', [AdminController::class, 'categories'])->name('categories.index');
            Route::post('/', [AdminController::class, 'createCategory'])->name('categories.store');
            Route::put('/{id}', [AdminController::class, 'updateCategory'])->name('categories.update');
            Route::delete('/{id}', [AdminController::class, 'deleteCategory'])->name('categories.destroy');
        });

        // সিস্টেম সেটিংস
        Route::prefix('settings')->group(function () {
            Route::get('/', [AdminController::class, 'settings'])->name('settings.index');
            Route::put('/', [AdminController::class, 'updateSettings'])->name('settings.update');
        });

        // অডিট লগ
        Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit-logs');
    });

// ===================
// File Download Routes
// ===================
Route::prefix('download')->group(function () {
    Route::get('/report/{id}', [DashboardController::class, 'downloadReport'])->name('download.report');
    Route::get('/invoice/{id}', [DashboardController::class, 'downloadInvoice'])->name('download.invoice');
    Route::get('/export/{type}', [DashboardController::class, 'exportData'])->name('download.export');
});

// ===================
// Webhook Routes
// ===================
Route::prefix('webhooks')->group(function () {
    // Stripe Webhook
    Route::post('/stripe', [WebhookController::class, 'handleStripe'])->name('webhooks.stripe');
    
    // PayPal Webhook
    Route::post('/paypal', [WebhookController::class, 'handlePayPal'])->name('webhooks.paypal');
    
    // bKash Webhook
    Route::post('/bkash', [WebhookController::class, 'handleBkash'])->name('webhooks.bkash');
    
    // SSLCommerz Webhook
    Route::post('/sslcommerz', [WebhookController::class, 'handleSSLCommerz'])->name('webhooks.sslcommerz');
    
    // GitHub Webhook
    Route::post('/github', [WebhookController::class, 'handleGithub'])->name('webhooks.github');
});

// ===================
// Sitemap Routes
// ===================
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [PageController::class, 'robots'])->name('robots');

// ===================
// RSS Feed
// ===================
Route::get('/feed.xml', [PageController::class, 'rssFeed'])->name('feed');
Route::get('/blog/feed.xml', [PageController::class, 'blogRssFeed'])->name('blog.feed');

// ===================
// Fallback Route
// ===================
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
