<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use App\Console\Commands\BackupDatabase;
use App\Console\Commands\GenerateReport;
use App\Console\Commands\SyncData;
use App\Console\Commands\ScheduleAutomation;
use App\Console\Commands\CleanupOldData;
use App\Console\Commands\OptimizeDatabase;
use App\Console\Commands\CacheWarmup;
use App\Console\Commands\CheckSystemHealth;
use App\Console\Commands\ProcessQueueJobs;
use App\Console\Commands\SendNotifications;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Custom Commands
        BackupDatabase::class,
        GenerateReport::class,
        SyncData::class,
        ScheduleAutomation::class,
        CleanupOldData::class,
        OptimizeDatabase::class,
        CacheWarmup::class,
        CheckSystemHealth::class,
        ProcessQueueJobs::class,
        SendNotifications::class,
        
        // Laravel Commands
        \Laravel\Horizon\Console\HorizonCommand::class,
        \Laravel\Telescope\Console\PruneCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // ===================
        // Database Backup
        // ===================
        
        // দৈনিক ডাটাবেস ব্যাকআপ (প্রতিদিন রাত ৩টায়)
        $schedule->command('backup:database')
            ->dailyAt('03:00')
            ->timezone('Asia/Dhaka')
            ->onFailure(function () {
                Log::error('Database backup failed');
            })
            ->onSuccess(function () {
                Log::info('Database backup completed successfully');
            })
            ->emailOutputTo('admin@toolkitpro.com');
        
        // সাপ্তাহিক ফুল ব্যাকআপ
        $schedule->command('backup:database --full')
            ->weeklyOn(0, '02:00')
            ->timezone('Asia/Dhaka');
        
        // ===================
        // Report Generation
        // ===================
        
        // দৈনিক রিপোর্ট
        $schedule->command('report:generate daily')
            ->dailyAt('23:59')
            ->timezone('Asia/Dhaka');
        
        // সাপ্তাহিক রিপোর্ট
        $schedule->command('report:generate weekly')
            ->weeklyOn(6, '23:59')
            ->timezone('Asia/Dhaka');
        
        // মাসিক রিপোর্ট
        $schedule->command('report:generate monthly')
            ->monthlyOn(1, '00:30')
            ->timezone('Asia/Dhaka');
        
        // ===================
        // Data Synchronization
        // ===================
        
        // প্রতি ৫ মিনিটে ডেটা সিঙ্ক
        $schedule->command('sync:data')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();
        
        // প্রতি ঘন্টায় ফুল সিঙ্ক
        $schedule->command('sync:data --full')
            ->hourly()
            ->withoutOverlapping();
        
        // ===================
        // Automation Scheduling
        // ===================
        
        // প্রতি মিনিটে অটোমেশন চেক
        $schedule->command('automation:schedule')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        
        // প্রতি ৫ মিনিটে অটোমেশন রান
        $schedule->command('automation:run')
            ->everyFiveMinutes()
            ->withoutOverlapping();
        
        // ===================
        // Cleanup Operations
        // ===================
        
        // পুরনো ডেটা ক্লিনআপ (প্রতিদিন)
        $schedule->command('cleanup:old-data')
            ->dailyAt('04:00')
            ->timezone('Asia/Dhaka');
        
        // টেম্পরারি ফাইল ক্লিনআপ (প্রতি ঘন্টা)
        $schedule->command('cleanup:old-data --temp')
            ->hourly();
        
        // ===================
        // Database Optimization
        // ===================
        
        // ডাটাবেস অপ্টিমাইজেশন (সাপ্তাহিক)
        $schedule->command('optimize:database')
            ->weeklyOn(0, '04:30')
            ->timezone('Asia/Dhaka');
        
        // ===================
        // Cache Management
        // ===================
        
        // ক্যাশ ওয়ার্মআপ (প্রতি ৩০ মিনিটে)
        $schedule->command('cache:warmup')
            ->everyThirtyMinutes()
            ->withoutOverlapping();
        
        // ক্যাশ ক্লিয়ার (প্রতিদিন)
        $schedule->command('cache:clear')
            ->dailyAt('05:00')
            ->timezone('Asia/Dhaka');
        
        // ===================
        // System Health Check
        // ===================
        
        // সিস্টেম হেলথ চেক (প্রতি ৫ মিনিটে)
        $schedule->command('system:health-check')
            ->everyFiveMinutes()
            ->withoutOverlapping();
        
        // ===================
        // Queue Processing
        // ===================
        
        // কিউ জব প্রসেসিং
        $schedule->command('queue:process')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        
        // ফেইলড জব রিট্রাই
        $schedule->command('queue:retry all')
            ->everyTenMinutes();
        
        // ===================
        // Notifications
        // ===================
        
        // নোটিফিকেশন পাঠানো
        $schedule->command('notifications:send')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        
        // ইমেইল নোটিফিকেশন
        $schedule->command('notifications:send --email')
            ->everyFiveMinutes();
        
        // ===================
        // Horizon Management
        // ===================
        
        // Horizon স্ন্যাপশট
        $schedule->command('horizon:snapshot')
            ->everyFiveMinutes();
        
        // Horizon টার্মিনেট
        $schedule->command('horizon:terminate')
            ->dailyAt('23:00');
        
        // ===================
        // Telescope Management
        // ===================
        
        // Telescope প্রুন
        $schedule->command('telescope:prune --hours=48')
            ->dailyAt('00:30');
        
        // ===================
        // Search Index Management
        // ===================
        
        // Elasticsearch ইনডেক্স রিফ্রেশ
        $schedule->command('scout:flush')
            ->weeklyOn(0, '01:00');
        
        $schedule->command('scout:import')
            ->weeklyOn(0, '02:00');
        
        // ===================
        // Blockchain Operations
        // ===================
        
        // ব্লকচেইন সিঙ্ক (প্রতি ৫ মিনিটে)
        $schedule->command('blockchain:sync')
            ->everyFiveMinutes()
            ->withoutOverlapping();
        
        // ===================
        // AI Model Updates
        // ===================
        
        // AI মডেল আপডেট (সাপ্তাহিক)
        $schedule->command('ai:update-models')
            ->weeklyOn(1, '03:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Get the timezone that should be used by default for scheduled events.
     *
     * @return \DateTimeZone|string|null
     */
    protected function scheduleTimezone(): \DateTimeZone|string|null
    {
        return 'Asia/Dhaka';
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commandsInConsole(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
