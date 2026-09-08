<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // টেস্টিং এনভায়রনমেন্ট কনফিগারেশন
        $this->configureTestingEnvironment($app);

        // টেস্টিং ডাটাবেস সেটআপ
        $this->setupTestingDatabase($app);

        // ক্যাশ কনফিগারেশন
        $this->configureCache();

        // সার্ভিস প্রোভাইডার রেজিস্ট্রেশন
        $this->registerTestingProviders($app);

        return $app;
    }

    /**
     * Configure testing environment.
     *
     * @param Application $app
     * @return void
     */
    protected function configureTestingEnvironment(Application $app): void
    {
        // এনভায়রনমেন্ট সেট করুন
        $app->detectEnvironment(function () {
            return 'testing';
        });

        // টেস্টিং কনফিগারেশন
        Config::set('app.env', 'testing');
        Config::set('app.debug', true);
        Config::set('app.key', 'base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10=');
        Config::set('app.url', 'http://localhost');

        // ডাটাবেস কনফিগারেশন
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // ক্যাশ কনফিগারেশন
        Config::set('cache.default', 'array');
        Config::set('cache.stores.array', [
            'driver' => 'array',
            'serialize' => false,
        ]);

        // সেশন কনফিগারেশন
        Config::set('session.driver', 'array');
        Config::set('session.lifetime', 120);
        Config::set('session.expire_on_close', false);
        Config::set('session.encrypt', false);
        Config::set('session.connection', null);
        Config::set('session.table', 'sessions');
        Config::set('session.store', null);
        Config::set('session.lottery', [2, 100]);
        Config::set('session.cookie', 'toolkit_test_session');
        Config::set('session.path', '/');
        Config::set('session.domain', null);
        Config::set('session.secure', null);
        Config::set('session.http_only', true);
        Config::set('session.same_site', 'lax');

        // কিউ কনফিগারেশন
        Config::set('queue.default', 'sync');
        Config::set('queue.connections.sync', [
            'driver' => 'sync',
        ]);

        // মেইল কনফিগারেশন
        Config::set('mail.default', 'array');
        Config::set('mail.mailers.array', [
            'transport' => 'array',
        ]);
        Config::set('mail.from', [
            'address' => 'test@toolkitpro.com',
            'name' => 'Toolkit Pro Test',
        ]);

        // ফাইলসিস্টেম কনফিগারেশন
        Config::set('filesystems.default', 'local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
            'root' => storage_path('app'),
        ]);
        Config::set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => '/storage',
            'visibility' => 'public',
        ]);

        // ব্রডকাস্টিং কনফিগারেশন
        Config::set('broadcasting.default', 'log');
        Config::set('broadcasting.connections.log', [
            'driver' => 'log',
        ]);
    }

    /**
     * Setup testing database.
     *
     * @param Application $app
     * @return void
     */
    protected function setupTestingDatabase(Application $app): void
    {
        // ইন-মেমোরি SQLite ডাটাবেস
        DB::statement('PRAGMA foreign_keys = ON');
        DB::statement('PRAGMA journal_mode = MEMORY');
        DB::statement('PRAGMA synchronous = OFF');
        DB::statement('PRAGMA cache_size = 10000');
        DB::statement('PRAGMA temp_store = MEMORY');

        // মাইগ্রেশন চালান
        Artisan::call('migrate:fresh', [
            '--force' => true,
            '--no-interaction' => true,
        ]);
    }

    /**
     * Configure cache for testing.
     *
     * @return void
     */
    protected function configureCache(): void
    {
        // সব ক্যাশ ক্লিয়ার করুন
        Cache::flush();
        
        // ক্যাশ ড্রাইভার সেট করুন
        Config::set('cache.default', 'array');
        
        // ক্যাশ প্রিফিক্স
        Config::set('cache.prefix', 'toolkit_test_');
    }

    /**
     * Register testing service providers.
     *
     * @param Application $app
     * @return void
     */
    protected function registerTestingProviders(Application $app): void
    {
        // টেস্টিং প্রোভাইডার রেজিস্টার করুন
        $app->register(\Tests\Providers\TestingServiceProvider::class);
    }

    /**
     * Setup test database transactions.
     *
     * @return void
     */
    protected function setupDatabaseTransactions(): void
    {
        DB::beginTransaction();
    }

    /**
     * Rollback test database transactions.
     *
     * @return void
     */
    protected function rollbackDatabaseTransactions(): void
    {
        DB::rollBack();
    }

    /**
     * Refresh database for testing.
     *
     * @return void
     */
    protected function refreshDatabase(): void
    {
        Artisan::call('migrate:refresh', [
            '--force' => true,
            '--no-interaction' => true,
            '--seed' => false,
        ]);
    }

    /**
     * Seed database for testing.
     *
     * @return void
     */
    protected function seedDatabase(): void
    {
        Artisan::call('db:seed', [
            '--force' => true,
            '--no-interaction' => true,
        ]);
    }

    /**
     * Run specific seeder.
     *
     * @param string $seeder
     * @return void
     */
    protected function runSeeder(string $seeder): void
    {
        Artisan::call('db:seed', [
            '--class' => $seeder,
            '--force' => true,
            '--no-interaction' => true,
        ]);
    }

    /**
     * Run specific migrations.
     *
     * @param string $path
     * @return void
     */
    protected function runMigrations(string $path): void
    {
        Artisan::call('migrate', [
            '--path' => $path,
            '--force' => true,
            '--no-interaction' => true,
        ]);
    }

    /**
     * Rollback specific migrations.
     *
     * @param string $path
     * @return void
     */
    protected function rollbackMigrations(string $path): void
    {
        Artisan::call('migrate:rollback', [
            '--path' => $path,
            '--force' => true,
            '--no-interaction' => true,
        ]);
    }

    /**
     * Clear application cache.
     *
     * @return void
     */
    protected function clearApplicationCache(): void
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('event:clear');
        Artisan::call('optimize:clear');
    }

    /**
     * Rebuild application cache.
     *
     * @return void
     */
    protected function rebuildApplicationCache(): void
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        Artisan::call('event:cache');
    }

    /**
     * Get application instance.
     *
     * @return Application
     */
    protected function getApplication(): Application
    {
        return $this->app;
    }

    /**
     * Get application version.
     *
     * @return string
     */
    protected function getApplicationVersion(): string
    {
        return $this->app->version();
    }

    /**
     * Check if application is in testing mode.
     *
     * @return bool
     */
    protected function isTesting(): bool
    {
        return $this->app->environment('testing');
    }

    /**
     * Get test database connection.
     *
     * @return string
     */
    protected function getTestDatabaseConnection(): string
    {
        return config('database.default');
    }

    /**
     * Get test database name.
     *
     * @return string
     */
    protected function getTestDatabaseName(): string
    {
        return config('database.connections.' . $this->getTestDatabaseConnection() . '.database');
    }
}
