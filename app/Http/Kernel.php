<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\RateLimit;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\LogRequests;
use App\Http\Middleware\CacheMiddleware;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TwoFactorAuthentication;
use App\Http\Middleware\ValidateApiKey;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\ValidateSignature;
use App\Http\Middleware\EnsureEmailIsVerified;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // Trust Proxies (Load Balancer, CDN)
        TrustProxies::class,
        
        // Handle CORS
        CorsMiddleware::class,
        
        // Prevent requests during maintenance
        PreventRequestsDuringMaintenance::class,
        
        // Validate post size
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        
        // Trim strings
        TrimStrings::class,
        
        // Convert empty strings to null
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        
        // Security Headers
        SecurityHeaders::class,
        
        // Log Requests
        LogRequests::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        // ===================
        // Web Middleware Group
        // ===================
        'web' => [
            EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            SetLocale::class,
        ],

        // ===================
        // API Middleware Group
        // ===================
        'api' => [
            // Rate limiting
            'throttle:api',
            
            // Substitute bindings
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            
            // Validate API key (optional)
            ValidateApiKey::class,
        ],
        
        // ===================
        // Admin Middleware Group
        // ===================
        'admin' => [
            'web',
            'auth',
            'verified',
            'role:admin,super-admin',
            'permission:access-admin',
            '2fa',
            RateLimit::class.':admin',
        ],
        
        // ===================
        // Dashboard Middleware Group
        // ===================
        'dashboard' => [
            'web',
            'auth',
            'verified',
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used instead of class names to conveniently assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        // Authentication
        'auth' => Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'guest' => RedirectIfAuthenticated::class,
        'verified' => EnsureEmailIsVerified::class,
        
        // Authorization
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'role' => CheckRole::class,
        'permission' => CheckPermission::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        
        // Security
        'signed' => ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        '2fa' => TwoFactorAuthentication::class,
        'api.key' => ValidateApiKey::class,
        
        // Performance
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'cache' => CacheMiddleware::class,
        'rate.limit' => RateLimit::class,
        
        // Localization
        'localize' => SetLocale::class,
        
        // Custom
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'cors' => CorsMiddleware::class,
        'security.headers' => SecurityHeaders::class,
        'log.requests' => LogRequests::class,
    ];

    /**
     * The priority-sorted list of middleware.
     *
     * This forces non-global middleware to always be in the given order.
     *
     * @var string[]
     */
    protected $middlewarePriority = [
        \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class,
        \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
        \Illuminate\Contracts\Session\Middleware\AuthenticatesSessions::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,
        
        // Custom Middleware
        'role' => CheckRole::class,
        'permission' => CheckPermission::class,
        'rate.limit' => RateLimit::class,
        'cache' => CacheMiddleware::class,
        'localize' => SetLocale::class,
        '2fa' => TwoFactorAuthentication::class,
        'api.key' => ValidateApiKey::class,
    ];

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting(): void
    {
        // API Rate Limiting
        \Illuminate\Support\Facades\RateLimiter::for('api', function ($request) {
            return \Illuminate\Support\Facades\RateLimiter::perMinute(
                config('api.rate_limit', 100)
            )->by($request->user()?->id ?: $request->ip());
        });

        // Admin Rate Limiting
        \Illuminate\Support\Facades\RateLimiter::for('admin', function ($request) {
            return \Illuminate\Support\Facades\RateLimiter::perMinute(60)
                ->by($request->user()?->id ?: $request->ip());
        });

        // Login Rate Limiting
        \Illuminate\Support\Facades\RateLimiter::for('login', function ($request) {
            return \Illuminate\Support\Facades\RateLimiter::perMinute(5)
                ->by($request->email ?: $request->ip());
        });

        // Search Rate Limiting
        \Illuminate\Support\Facades\RateLimiter::for('search', function ($request) {
            return \Illuminate\Support\Facades\RateLimiter::perMinute(30)
                ->by($request->ip());
        });
    }
}
