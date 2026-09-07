<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
| Toolkit Pro v2.0 - বিশ্বের সবচেয়ে সম্পূর্ণ অনলাইন টুলস প্ল্যাটফর্ম
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// Global Middleware
$app->middleware([
    // Trust Proxies
    App\Http\Middleware\TrustProxies::class,
    
    // Handle CORS
    App\Http\Middleware\CorsMiddleware::class,
    
    // Prevent requests during maintenance
    App\Http\Middleware\PreventRequestsDuringMaintenance::class,
    
    // Validate post size
    Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
    
    // Trim strings
    App\Http\Middleware\TrimStrings::class,
    
    // Convert empty strings to null
    Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    
    // Security Headers
    App\Http\Middleware\SecurityHeaders::class,
    
    // Log Requests
    App\Http\Middleware\LogRequests::class,
]);

// Middleware Groups
$app->middlewareGroup('web', [
    App\Http\Middleware\EncryptCookies::class,
    Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    Illuminate\Session\Middleware\StartSession::class,
    Illuminate\View\Middleware\ShareErrorsFromSession::class,
    App\Http\Middleware\VerifyCsrfToken::class,
    Illuminate\Routing\Middleware\SubstituteBindings::class,
]);

$app->middlewareGroup('api', [
    // Throttle requests
    'throttle:api',
    
    // Substitute bindings
    Illuminate\Routing\Middleware\SubstituteBindings::class,
]);

// Middleware Aliases
$app->middlewareAlias([
    'auth' => App\Http\Middleware\Authenticate::class,
    'auth.basic' => Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    'auth.session' => Illuminate\Session\Middleware\AuthenticateSession::class,
    'cache.headers' => Illuminate\Http\Middleware\SetCacheHeaders::class,
    'can' => Illuminate\Auth\Middleware\Authorize::class,
    'guest' => App\Http\Middleware\RedirectIfAuthenticated::class,
    'password.confirm' => Illuminate\Auth\Middleware\RequirePassword::class,
    'precognitive' => Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
    'signed' => App\Http\Middleware\ValidateSignature::class,
    'throttle' => Illuminate\Routing\Middleware\ThrottleRequests::class,
    'verified' => Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    'role' => App\Http\Middleware\CheckRole::class,
    'permission' => App\Http\Middleware\CheckPermission::class,
    'rate.limit' => App\Http\Middleware\RateLimit::class,
    'cache' => App\Http\Middleware\CacheMiddleware::class,
    'localize' => App\Http\Middleware\SetLocale::class,
    '2fa' => App\Http\Middleware\TwoFactorAuthentication::class,
    'api.key' => App\Http\Middleware\ValidateApiKey::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, but typically you'd want to register them here.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(App\Providers\RouteServiceProvider::class);
$app->register(App\Providers\BroadcastServiceProvider::class);
$app->register(App\Providers\HorizonServiceProvider::class);
$app->register(App\Providers\TelescopeServiceProvider::class);

// Custom Service Providers
$app->register(App\Providers\ToolkitServiceProvider::class);
$app->register(App\Providers\AIServiceProvider::class);
$app->register(App\Providers\BlockchainServiceProvider::class);
$app->register(App\Providers\MarketplaceServiceProvider::class);
$app->register(App\Providers\GamificationServiceProvider::class);
$app->register(App\Providers\AutomationServiceProvider::class);
$app->register(App\Providers\AnalyticsServiceProvider::class);
$app->register(App\Providers\SearchServiceProvider::class);
$app->register(App\Providers\NotificationServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Register Aliases
|--------------------------------------------------------------------------
|
| Aliases are registered here for the convenience of the developer. These
| aliases are loaded when the application starts and can be used anywhere
| in the application.
|
*/

$app->alias('App', Illuminate\Support\Facades\App::class);
$app->alias('Arr', Illuminate\Support\Arr::class);
$app->alias('Artisan', Illuminate\Support\Facades\Artisan::class);
$app->alias('Auth', Illuminate\Support\Facades\Auth::class);
$app->alias('Blade', Illuminate\Support\Facades\Blade::class);
$app->alias('Broadcast', Illuminate\Support\Facades\Broadcast::class);
$app->alias('Bus', Illuminate\Support\Facades\Bus::class);
$app->alias('Cache', Illuminate\Support\Facades\Cache::class);
$app->alias('Config', Illuminate\Support\Facades\Config::class);
$app->alias('Cookie', Illuminate\Support\Facades\Cookie::class);
$app->alias('Crypt', Illuminate\Support\Facades\Crypt::class);
$app->alias('Date', Illuminate\Support\Facades\Date::class);
$app->alias('DB', Illuminate\Support\Facades\DB::class);
$app->alias('Eloquent', Illuminate\Database\Eloquent\Model::class);
$app->alias('Event', Illuminate\Support\Facades\Event::class);
$app->alias('File', Illuminate\Support\Facades\File::class);
$app->alias('Gate', Illuminate\Support\Facades\Gate::class);
$app->alias('Hash', Illuminate\Support\Facades\Hash::class);
$app->alias('Http', Illuminate\Support\Facades\Http::class);
$app->alias('Js', Illuminate\Support\Js::class);
$app->alias('Lang', Illuminate\Support\Facades\Lang::class);
$app->alias('Log', Illuminate\Support\Facades\Log::class);
$app->alias('Mail', Illuminate\Support\Facades\Mail::class);
$app->alias('Notification', Illuminate\Support\Facades\Notification::class);
$app->alias('Password', Illuminate\Support\Facades\Password::class);
$app->alias('Queue', Illuminate\Support\Facades\Queue::class);
$app->alias('RateLimiter', Illuminate\Support\Facades\RateLimiter::class);
$app->alias('Redirect', Illuminate\Support\Facades\Redirect::class);
$app->alias('Request', Illuminate\Support\Facades\Request::class);
$app->alias('Response', Illuminate\Support\Facades\Response::class);
$app->alias('Route', Illuminate\Support\Facades\Route::class);
$app->alias('Schema', Illuminate\Support\Facades\Schema::class);
$app->alias('Session', Illuminate\Support\Facades\Session::class);
$app->alias('Storage', Illuminate\Support\Facades\Storage::class);
$app->alias('Str', Illuminate\Support\Str::class);
$app->alias('URL', Illuminate\Support\Facades\URL::class);
$app->alias('Validator', Illuminate\Support\Facades\Validator::class);
$app->alias('View', Illuminate\Support\Facades\View::class);
$app->alias('Vite', Illuminate\Support\Facades\Vite::class);

// Custom Aliases
$app->alias('Toolkit', App\Facades\Toolkit::class);
$app->alias('AIEngine', App\Facades\AIEngine::class);
$app->alias('Automation', App\Facades\Automation::class);
$app->alias('Blockchain', App\Facades\Blockchain::class);
$app->alias('Marketplace', App\Facades\Marketplace::class);
$app->alias('Gamification', App\Facades\Gamification::class);
$app->alias('Analytics', App\Facades\Analytics::class);
$app->alias('Search', App\Facades\Search::class);
$app->alias('Notification', App\Facades\Notification::class);
$app->alias('Collaboration', App\Facades\Collaboration::class);

/*
|--------------------------------------------------------------------------
| Configure Application
|--------------------------------------------------------------------------
|
| Here we will configure the application based on the environment.
|
*/

// Set timezone
date_default_timezone_set(config('app.timezone', 'Asia/Dhaka'));

// Set locale
setlocale(LC_ALL, config('app.locale', 'bn_BD.UTF-8'));

// Enable OPcache for production
if ($app->environment('production')) {
    ini_set('opcache.enable', '1');
    ini_set('opcache.memory_consumption', '256');
    ini_set('opcache.max_accelerated_files', '20000');
    ini_set('opcache.revalidate_freq', '2');
}

// Configure error reporting
if ($app->environment('local', 'development')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
}

// Set memory limit
ini_set('memory_limit', '512M');

// Set execution time
ini_set('max_execution_time', '300');

// Set upload limits
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
