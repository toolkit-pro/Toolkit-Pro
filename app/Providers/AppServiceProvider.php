<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Category;
use App\Models\Tool;
use App\Models\Setting;
use App\Services\IntelligentToolEngine;
use App\Services\WorkflowAutomation;
use App\Services\MultiModalInput;
use App\Services\CrossPlatformSync;
use App\Services\SmartToolSearch;
use App\Services\AutomationEngine;
use App\Services\SmartScheduler;
use App\Services\AutoScaling;
use App\Services\UserBehaviorAnalyzer;
use App\Services\SentimentAnalyzer;
use App\Services\CLVAnalyzer;
use App\Services\SmartDashboard;
use App\Services\AdvancedReporting;
use App\Services\GenerativeAI;
use App\Services\PredictiveAnalytics;
use App\Services\RealTimeCollaboration;
use App\Services\GamificationEngine;
use App\Services\SocialEcosystem;
use App\Services\BlockchainIntegration;
use App\Services\VoiceAndARVR;
use App\Services\GlobalMarketplace;
use App\Services\SecurityFeatures;
use App\Services\CacheService;
use App\Services\QueueService;
use App\Services\NotificationService;
use App\Services\EmailService;
use App\Services\StorageService;
use App\Services\ValidationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // সার্ভিস বাইন্ডিং
        $this->registerServices();
        
        // কনফিগারেশন মর্জ
        $this->mergeConfigurations();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // ডাটাবেস কনফিগারেশন
        $this->configureDatabase();
        
        // URL কনফিগারেশন
        $this->configureURL();
        
        // পেজিনেশন
        $this->configurePagination();
        
        // ভ্যালিডেশন
        $this->configureValidation();
        
        // ব্লেড ডিরেক্টিভ
        $this->registerBladeDirectives();
        
        // ভিউ কম্পোজার
        $this->registerViewComposers();
        
        // গেট পলিসি
        $this->registerPolicies();
        
        // মডেল কনফিগারেশন
        $this->configureModels();
        
        // গ্লোবাল ভেরিয়েবল
        $this->shareGlobalVariables();
    }

    /**
     * Register application services.
     *
     * @return void
     */
    protected function registerServices(): void
    {
        // AI সার্ভিস
        $this->app->singleton(IntelligentToolEngine::class, function ($app) {
            return new IntelligentToolEngine();
        });
        
        // অটোমেশন সার্ভিস
        $this->app->singleton(WorkflowAutomation::class, function ($app) {
            return new WorkflowAutomation();
        });
        
        $this->app->singleton(AutomationEngine::class, function ($app) {
            return new AutomationEngine();
        });
        
        // মাল্টি-মডাল ইনপুট
        $this->app->singleton(MultiModalInput::class, function ($app) {
            return new MultiModalInput();
        });
        
        // ক্রস-প্ল্যাটফর্ম সিঙ্ক
        $this->app->singleton(CrossPlatformSync::class, function ($app) {
            return new CrossPlatformSync();
        });
        
        // স্মার্ট সার্চ
        $this->app->singleton(SmartToolSearch::class, function ($app) {
            return new SmartToolSearch();
        });
        
        // শিডিউলার
        $this->app->singleton(SmartScheduler::class, function ($app) {
            return new SmartScheduler();
        });
        
        // অটো-স্কেলিং
        $this->app->singleton(AutoScaling::class, function ($app) {
            return new AutoScaling();
        });
        
        // ইউজার বিহেভিয়ার অ্যানালাইসিস
        $this->app->singleton(UserBehaviorAnalyzer::class, function ($app) {
            return new UserBehaviorAnalyzer();
        });
        
        // সেন্টিমেন্ট অ্যানালাইসিস
        $this->app->singleton(SentimentAnalyzer::class, function ($app) {
            return new SentimentAnalyzer();
        });
        
        // CLV অ্যানালাইসিস
        $this->app->singleton(CLVAnalyzer::class, function ($app) {
            return new CLVAnalyzer();
        });
        
        // স্মার্ট ড্যাশবোর্ড
        $this->app->singleton(SmartDashboard::class, function ($app) {
            return new SmartDashboard();
        });
        
        // রিপোর্টিং
        $this->app->singleton(AdvancedReporting::class, function ($app) {
            return new AdvancedReporting();
        });
        
        // জেনারেটিভ AI
        $this->app->singleton(GenerativeAI::class, function ($app) {
            return new GenerativeAI();
        });
        
        // প্রেডিক্টিভ অ্যানালিটিক্স
        $this->app->singleton(PredictiveAnalytics::class, function ($app) {
            return new PredictiveAnalytics();
        });
        
        // রিয়েল-টাইম কোলাবোরেশন
        $this->app->singleton(RealTimeCollaboration::class, function ($app) {
            return new RealTimeCollaboration();
        });
        
        // গেমিফিকেশন
        $this->app->singleton(GamificationEngine::class, function ($app) {
            return new GamificationEngine();
        });
        
        // সোশ্যাল ইকোসিস্টেম
        $this->app->singleton(SocialEcosystem::class, function ($app) {
            return new SocialEcosystem();
        });
        
        // ব্লকচেইন
        $this->app->singleton(BlockchainIntegration::class, function ($app) {
            return new BlockchainIntegration();
        });
        
        // ভয়েস ও AR/VR
        $this->app->singleton(VoiceAndARVR::class, function ($app) {
            return new VoiceAndARVR();
        });
        
        // গ্লোবাল মার্কেটপ্লেস
        $this->app->singleton(GlobalMarketplace::class, function ($app) {
            return new GlobalMarketplace();
        });
        
        // সিকিউরিটি
        $this->app->singleton(SecurityFeatures::class, function ($app) {
            return new SecurityFeatures();
        });
        
        // ক্যাশ সার্ভিস
        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService();
        });
        
        // কিউ সার্ভিস
        $this->app->singleton(QueueService::class, function ($app) {
            return new QueueService();
        });
        
        // নোটিফিকেশন সার্ভিস
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });
        
        // ইমেইল সার্ভিস
        $this->app->singleton(EmailService::class, function ($app) {
            return new EmailService();
        });
        
        // স্টোরেজ সার্ভিস
        $this->app->singleton(StorageService::class, function ($app) {
            return new StorageService();
        });
        
        // ভ্যালিডেশন সার্ভিস
        $this->app->singleton(ValidationService::class, function ($app) {
            return new ValidationService();
        });
    }

    /**
     * Merge custom configurations.
     *
     * @return void
     */
    protected function mergeConfigurations(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/toolkit.php', 'toolkit');
        $this->mergeConfigFrom(__DIR__.'/../../config/ai.php', 'ai');
        $this->mergeConfigFrom(__DIR__.'/../../config/blockchain.php', 'blockchain');
        $this->mergeConfigFrom(__DIR__.'/../../config/marketplace.php', 'marketplace');
        $this->mergeConfigFrom(__DIR__.'/../../config/gamification.php', 'gamification');
        $this->mergeConfigFrom(__DIR__.'/../../config/automation.php', 'automation');
        $this->mergeConfigFrom(__DIR__.'/../../config/analytics.php', 'analytics');
        $this->mergeConfigFrom(__DIR__.'/../../config/search.php', 'search');
        $this->mergeConfigFrom(__DIR__.'/../../config/notification.php', 'notification');
    }

    /**
     * Configure database settings.
     *
     * @return void
     */
    protected function configureDatabase(): void
    {
        // MySQL/MariaDB জন্য
        Schema::defaultStringLength(191);
        
        // ডাটাবেস স্ট্রিক্ট মোড
        Model::shouldBeStrict(!$this->app->isProduction());
        
        // ইগার লোডিং প্রিভেনশন
        Model::preventLazyLoading(!$this->app->isProduction());
        
        // সাইলেন্টলি ডিসকার্ড ফিলস
        Model::preventSilentlyDiscardingAttributes(!$this->app->isProduction());
    }

    /**
     * Configure URL settings.
     *
     * @return void
     */
    protected function configureURL(): void
    {
        // প্রোডাকশনে HTTPS ফোর্স
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Configure pagination.
     *
     * @return void
     */
    protected function configurePagination(): void
    {
        // Tailwind CSS পেজিনেশন
        Paginator::useTailwind();
    }

    /**
     * Configure validation.
     *
     * @return void
     */
    protected function configureValidation(): void
    {
        // কাস্টম ভ্যালিডেশন রুল
        Validator::extend('phone', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[0-9+\-\s()]{10,20}$/', $value);
        }, 'The :attribute must be a valid phone number.');

        Validator::extend('slug', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value);
        }, 'The :attribute must be a valid slug.');

        Validator::extend('hex_color', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value);
        }, 'The :attribute must be a valid hex color.');
    }

    /**
     * Register Blade directives.
     *
     * @return void
     */
    protected function registerBladeDirectives(): void
    {
        // @active রুট চেক
        Blade::directive('active', function ($expression) {
            return "<?php echo request()->routeIs($expression) ? 'active' : ''; ?>";
        });

        // @selected ড্রপডাউন
        Blade::directive('selected', function ($expression) {
            return "<?php echo $expression ? 'selected' : ''; ?>";
        });

        // @checked চেকবক্স
        Blade::directive('checked', function ($expression) {
            return "<?php echo $expression ? 'checked' : ''; ?>";
        });

        // @disabled ডিসেবল
        Blade::directive('disabled', function ($expression) {
            return "<?php echo $expression ? 'disabled' : ''; ?>";
        });

        // @datetime ফরম্যাট
        Blade::directive('datetime', function ($expression) {
            return "<?php echo $expression ? $expression->format('d/m/Y H:i:s') : ''; ?>";
        });

        // @money কারেন্সি
        Blade::directive('money', function ($expression) {
            return "<?php echo number_format($expression, 2) . ' ' . config('app.currency', 'USD'); ?>";
        });
    }

    /**
     * Register view composers.
     *
     * @return void
     */
    protected function registerViewComposers(): void
    {
        // নেভবার জন্য ক্যাটাগরি
        View::composer('partials.navbar', function ($view) {
            $categories = Cache::remember('navbar_categories', 3600, function () {
                return Category::active()
                    ->parents()
                    ->with('children')
                    ->orderBy('order')
                    ->get();
            });
            
            $view->with('navCategories', $categories);
        });

        // ফুটারের জন্য সেটিংস
        View::composer('partials.footer', function ($view) {
            $settings = Cache::remember('footer_settings', 3600, function () {
                return Setting::where('group', 'footer')->get();
            });
            
            $view->with('footerSettings', $settings);
        });

        // সাইডবারের জন্য ইউজার স্ট্যাটস
        View::composer('partials.sidebar', function ($view) {
            if (auth()->check()) {
                $stats = Cache::remember('user_stats_' . auth()->id(), 300, function () {
                    return auth()->user()->getToolUsageStats();
                });
                
                $view->with('userStats', $stats);
            }
        });
    }

    /**
     * Register policies.
     *
     * @return void
     */
    protected function registerPolicies(): void
    {
        // Tool পলিসি
        Gate::define('update-tool', function ($user, $tool) {
            return $user->id === $tool->creator_id || $user->hasRole(['admin', 'super-admin']);
        });

        Gate::define('delete-tool', function ($user, $tool) {
            return $user->id === $tool->creator_id || $user->hasRole(['admin', 'super-admin']);
        });
    }

    /**
     * Configure models.
     *
     * @return void
     */
    protected function configureModels(): void
    {
        // মডেল ইভেন্ট লগিং
        Model::setEventDispatcher($this->app['events']);
    }

    /**
     * Share global variables.
     *
     * @return void
     */
    protected function shareGlobalVariables(): void
    {
        // অ্যাপ্লিকেশন সেটিংস
        View::share('appName', config('app.name', 'Toolkit Pro'));
        View::share('appVersion', config('app.settings.version', '2.0.0'));
        View::share('appLocale', app()->getLocale());
        
        // ফিচার ফ্ল্যাগ
        View::share('features', config('app.features'));
        
        // ল্যাঙ্গুয়েজ
        View::share('languages', config('app.languages'));
        
        // কারেন্সি
        View::share('currencies', config('app.currencies'));
        
        // ডেট ফরম্যাট
        View::share('dateFormats', config('app.date_formats'));
    }
}
