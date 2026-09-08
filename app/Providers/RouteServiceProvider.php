<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * The path to the "admin" route for your application.
     *
     * @var string
     */
    public const ADMIN = '/admin';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot(): void
    {
        // রেট লিমিটার কনফিগারেশন
        $this->configureRateLimiting();

        // রুট মডেল বাইন্ডিং
        $this->configureRouteModelBindings();

        // রুট প্যাটার্ন
        $this->configureRoutePatterns();

        $this->routes(function () {
            // API রুট
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // ওয়েব রুট
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // অ্যাডমিন রুট
            Route::middleware(['web', 'auth', 'role:admin,super-admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            // চ্যানেল রুট (WebSocket)
            Route::middleware('web')
                ->group(base_path('routes/channels.php'));

            // কনসোল রুট
            Route::middleware('web')
                ->group(base_path('routes/console.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting(): void
    {
        // API রেট লিমিটার
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();
            
            // ইউজার ভিত্তিক লিমিট
            if ($user && $user->hasRole('super-admin')) {
                return Limit::perMinute(1000)->by($user->id);
            }
            
            if ($user && $user->hasRole('admin')) {
                return Limit::perMinute(500)->by($user->id);
            }
            
            if ($user && $user->hasRole('premium-user')) {
                return Limit::perMinute(200)->by($user->id);
            }
            
            if ($user) {
                return Limit::perMinute(100)->by($user->id);
            }
            
            // গেস্ট ইউজার
            return Limit::perMinute(60)->by($request->ip());
        });

        // অথেনটিকেশন রেট লিমিটার
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // লগইন রেট লিমিটার
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->input('email') . '|' . $request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        // রেজিস্ট্রেশন রেট লিমিটার
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(10)->by($request->ip());
        });

        // সার্চ রেট লিমিটার
        RateLimiter::for('search', function (Request $request) {
            return [
                Limit::perMinute(30)->by($request->ip()),
                Limit::perMinute(100)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // আপলোড রেট লিমিটার
        RateLimiter::for('upload', function (Request $request) {
            return Limit::perHour(50)->by($request->user()?->id ?: $request->ip());
        });

        // ডাউনলোড রেট লিমিটার
        RateLimiter::for('download', function (Request $request) {
            return Limit::perHour(100)->by($request->user()?->id ?: $request->ip());
        });

        // রিপোর্ট রেট লিমিটার
        RateLimiter::for('report', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
        });

        // অটোমেশন রেট লিমিটার
        RateLimiter::for('automation', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // মার্কেটপ্লেস রেট লিমিটার
        RateLimiter::for('marketplace', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // নোটিফিকেশন রেট লিমিটার
        RateLimiter::for('notification', function (Request $request) {
            return Limit::perMinute(50)->by($request->user()?->id ?: $request->ip());
        });

        // অ্যাডমিন রেট লিমিটার
        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Configure route model bindings.
     *
     * @return void
     */
    protected function configureRouteModelBindings(): void
    {
        // Tool মডেল বাইন্ডিং (slug দ্বারা)
        Route::bind('tool', function ($value) {
            return \App\Models\Tool::where('slug', $value)
                ->orWhere('id', $value)
                ->firstOrFail();
        });

        // Category মডেল বাইন্ডিং (slug দ্বারা)
        Route::bind('category', function ($value) {
            return \App\Models\Category::where('slug', $value)
                ->orWhere('id', $value)
                ->firstOrFail();
        });

        // User মডেল বাইন্ডিং (id দ্বারা)
        Route::bind('user', function ($value) {
            return \App\Models\User::findOrFail($value);
        });

        // Automation মডেল বাইন্ডিং
        Route::bind('automation', function ($value) {
            return \App\Models\Automation::findOrFail($value);
        });

        // Report মডেল বাইন্ডিং
        Route::bind('report', function ($value) {
            return \App\Models\Report::findOrFail($value);
        });

        // Badge মডেল বাইন্ডিং
        Route::bind('badge', function ($value) {
            return \App\Models\Badge::where('slug', $value)
                ->orWhere('id', $value)
                ->firstOrFail();
        });
    }

    /**
     * Configure route patterns.
     *
     * @return void
     */
    protected function configureRoutePatterns(): void
    {
        // ID প্যাটার্ন
        Route::pattern('id', '[0-9]+');
        
        // Slug প্যাটার্ন
        Route::pattern('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');
        
        // UUID প্যাটার্ন
        Route::pattern('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
        
        // ভার্সন প্যাটার্ন
        Route::pattern('version', 'v[0-9]+');
        
        // ল্যাঙ্গুয়েজ প্যাটার্ন
        Route::pattern('locale', 'bn|en|hi|ar|es|fr|de|pt|ru|zh|ja|ko');
        
        // টোকেন প্যাটার্ন
        Route::pattern('token', '[a-zA-Z0-9]{32,64}');
    }
}
