<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Toolkit Pro'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => env('APP_LOCALE', 'bn'),

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */
        Spatie\Permission\PermissionServiceProvider::class,
        Spatie\Activitylog\ActivitylogServiceProvider::class,
        Spatie\MediaLibrary\MediaLibraryServiceProvider::class,
        Spatie\Backup\BackupServiceProvider::class,
        Spatie\Sitemap\SitemapServiceProvider::class,
        Spatie\Translatable\TranslatableServiceProvider::class,
        Barryvdh\DomPDF\ServiceProvider::class,
        Maatwebsite\Excel\ExcelServiceProvider::class,
        Intervention\Image\ImageServiceProvider::class,
        Laravel\Horizon\HorizonServiceProvider::class,
        Laravel\Telescope\TelescopeServiceProvider::class,
        Laravel\Sanctum\SanctumServiceProvider::class,
        Laravel\Scout\ScoutServiceProvider::class,
        BeyondCode\LaravelWebSockets\WebSocketsServiceProvider::class,
        Sentry\Laravel\ServiceProvider::class,

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Providers\HorizonServiceProvider::class,
        App\Providers\TelescopeServiceProvider::class,
        
        // Custom Service Providers
        App\Providers\ToolkitServiceProvider::class,
        App\Providers\AIServiceProvider::class,
        App\Providers\BlockchainServiceProvider::class,
        App\Providers\MarketplaceServiceProvider::class,
        App\Providers\GamificationServiceProvider::class,
        App\Providers\AutomationServiceProvider::class,
        App\Providers\AnalyticsServiceProvider::class,
        App\Providers\SearchServiceProvider::class,
        App\Providers\NotificationServiceProvider::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        // Custom Facades
        'Toolkit' => App\Facades\Toolkit::class,
        'AIEngine' => App\Facades\AIEngine::class,
        'Automation' => App\Facades\Automation::class,
        'Blockchain' => App\Facades\Blockchain::class,
        'Marketplace' => App\Facades\Marketplace::class,
        'Gamification' => App\Facades\Gamification::class,
        'Analytics' => App\Facades\Analytics::class,
        'Search' => App\Facades\Search::class,
        'Notification' => App\Facades\Notification::class,
        'Collaboration' => App\Facades\Collaboration::class,
        'Excel' => Maatwebsite\Excel\Facades\Excel::class,
        'PDF' => Barryvdh\DomPDF\Facade::class,
        'Image' => Intervention\Image\Facades\Image::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Application Settings
    |--------------------------------------------------------------------------
    */

    // Toolkit Pro Specific Settings
    'settings' => [
        'site_name' => env('APP_NAME', 'Toolkit Pro'),
        'site_description' => 'বিশ্বের সবচেয়ে সম্পূর্ণ ও শক্তিশালী অনলাইন টুলস প্ল্যাটফর্ম',
        'site_keywords' => 'toolkit, online tools, ai tools, automation, blockchain',
        'site_url' => env('APP_URL', 'http://localhost'),
        'admin_email' => env('ADMIN_EMAIL', 'admin@toolkitpro.com'),
        'support_email' => env('SUPPORT_EMAIL', 'support@toolkitpro.com'),
        'contact_email' => env('CONTACT_EMAIL', 'contact@toolkitpro.com'),
        'version' => '2.0.0',
    ],

    // API Configuration
    'api' => [
        'version' => 'v1',
        'rate_limit' => env('API_RATE_LIMIT', 100),
        'rate_limit_pro' => env('API_RATE_LIMIT_PRO', 10000),
        'rate_limit_enterprise' => env('API_RATE_LIMIT_ENTERPRISE', 100000),
    ],

    // Feature Flags
    'features' => [
        'ai_enabled' => env('FEATURE_AI_ENABLED', true),
        'blockchain_enabled' => env('FEATURE_BLOCKCHAIN_ENABLED', true),
        'marketplace_enabled' => env('FEATURE_MARKETPLACE_ENABLED', true),
        'social_enabled' => env('FEATURE_SOCIAL_ENABLED', true),
        'gamification_enabled' => env('FEATURE_GAMIFICATION_ENABLED', true),
        'ar_vr_enabled' => env('FEATURE_AR_VR_ENABLED', true),
        'voice_enabled' => env('FEATURE_VOICE_ENABLED', true),
        'collaboration_enabled' => env('FEATURE_COLLABORATION_ENABLED', true),
    ],

    // Supported Languages
    'languages' => [
        'bn' => ['name' => 'বাংলা', 'flag' => '🇧🇩', 'rtl' => false],
        'en' => ['name' => 'English', 'flag' => '🇺🇸', 'rtl' => false],
        'hi' => ['name' => 'हिन्दी', 'flag' => '🇮🇳', 'rtl' => false],
        'ar' => ['name' => 'العربية', 'flag' => '🇸🇦', 'rtl' => true],
        'es' => ['name' => 'Español', 'flag' => '🇪🇸', 'rtl' => false],
        'fr' => ['name' => 'Français', 'flag' => '🇫🇷', 'rtl' => false],
        'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪', 'rtl' => false],
        'pt' => ['name' => 'Português', 'flag' => '🇧🇷', 'rtl' => false],
        'ru' => ['name' => 'Русский', 'flag' => '🇷🇺', 'rtl' => false],
        'zh' => ['name' => '中文', 'flag' => '🇨🇳', 'rtl' => false],
        'ja' => ['name' => '日本語', 'flag' => '🇯🇵', 'rtl' => false],
        'ko' => ['name' => '한국어', 'flag' => '🇰🇷', 'rtl' => false],
    ],

    // Date Formats
    'date_formats' => [
        'short' => 'd/m/Y',
        'medium' => 'd M, Y',
        'long' => 'd F, Y',
        'full' => 'l, d F, Y',
        'time' => 'H:i:s',
        'datetime' => 'd/m/Y H:i:s',
    ],

    // Currency Configuration
    'currencies' => [
        'USD' => ['symbol' => '$', 'name' => 'US Dollar'],
        'BDT' => ['symbol' => '৳', 'name' => 'Bangladeshi Taka'],
        'EUR' => ['symbol' => '€', 'name' => 'Euro'],
        'GBP' => ['symbol' => '£', 'name' => 'British Pound'],
        'INR' => ['symbol' => '₹', 'name' => 'Indian Rupee'],
        'BTC' => ['symbol' => '₿', 'name' => 'Bitcoin'],
        'ETH' => ['symbol' => 'Ξ', 'name' => 'Ethereum'],
    ],
];
