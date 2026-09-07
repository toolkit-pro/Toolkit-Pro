<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Tool;
use App\Models\Category;
use App\Models\Badge;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 Toolkit Pro Database Seeder Started...');
        
        try {
            DB::beginTransaction();
            
            // ===================
            // ১. রোল ও পারমিশন
            // ===================
            $this->command->info('Creating Roles and Permissions...');
            $this->call(RolePermissionSeeder::class);
            
            // ===================
            // ২. ইউজার
            // ===================
            $this->command->info('Creating Users...');
            $this->call(UserSeeder::class);
            
            // ===================
            // ৩. ক্যাটাগরি
            // ===================
            $this->command->info('Creating Categories...');
            $this->call(CategorySeeder::class);
            
            // ===================
            // ৪. টুলস
            // ===================
            $this->command->info('Creating Tools...');
            $this->call(ToolSeeder::class);
            
            // ===================
            // ৫. ব্যাজ
            // ===================
            $this->command->info('Creating Badges...');
            $this->call(BadgeSeeder::class);
            
            // ===================
            // ৬. সেটিংস
            // ===================
            $this->command->info('Creating Settings...');
            $this->call(SettingSeeder::class);
            
            DB::commit();
            
            // ===================
            // ৭. ক্যাশ ক্লিয়ার
            // ===================
            $this->command->info('Clearing Cache...');
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            // ===================
            // ৮. স্টোরেজ লিংক
            // ===================
            $this->command->info('Creating Storage Link...');
            Artisan::call('storage:link');
            
            $this->command->info('✅ Database Seeding Completed Successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->command->error('❌ Database Seeding Failed!');
            $this->command->error('Error: ' . $e->getMessage());
            $this->command->error('File: ' . $e->getFile() . ':' . $e->getLine());
            
            Log::error('Database Seeding Failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Create default roles and permissions.
     *
     * @return void
     */
    protected function createRolesAndPermissions(): void
    {
        // রোল তৈরি
        $roles = [
            'super-admin' => 'সুপার অ্যাডমিন',
            'admin' => 'অ্যাডমিন',
            'moderator' => 'মডারেটর',
            'editor' => 'এডিটর',
            'user' => 'সাধারণ ইউজার',
            'premium-user' => 'প্রিমিয়াম ইউজার',
            'developer' => 'ডেভেলপার',
            'seller' => 'বিক্রেতা',
        ];

        foreach ($roles as $name => $displayName) {
            Role::firstOrCreate(
                ['name' => $name],
                ['display_name' => $displayName, 'guard_name' => 'web']
            );
        }

        // পারমিশন তৈরি
        $permissions = [
            // ইউজার পারমিশন
            'view-users', 'create-users', 'edit-users', 'delete-users',
            'view-profile', 'edit-profile', 'delete-profile',
            
            // টুল পারমিশন
            'view-tools', 'create-tools', 'edit-tools', 'delete-tools',
            'approve-tools', 'reject-tools', 'feature-tools',
            
            // ক্যাটাগরি পারমিশন
            'view-categories', 'create-categories', 'edit-categories', 'delete-categories',
            
            // রোল পারমিশন
            'view-roles', 'create-roles', 'edit-roles', 'delete-roles',
            'assign-roles', 'revoke-roles',
            
            // পারমিশন পারমিশন
            'view-permissions', 'create-permissions', 'edit-permissions', 'delete-permissions',
            'assign-permissions', 'revoke-permissions',
            
            // অটোমেশন পারমিশন
            'view-automations', 'create-automations', 'edit-automations', 'delete-automations',
            'run-automations',
            
            // রিপোর্ট পারমিশন
            'view-reports', 'create-reports', 'download-reports', 'delete-reports',
            
            // অ্যানালিটিক্স পারমিশন
            'view-analytics', 'export-analytics',
            
            // মার্কেটপ্লেস পারমিশন
            'view-marketplace', 'create-listings', 'edit-listings', 'delete-listings',
            'purchase-tools', 'sell-tools',
            
            // সেটিংস পারমিশন
            'view-settings', 'edit-settings',
            
            // সিস্টেম পারমিশন
            'view-logs', 'clear-cache', 'backup-database', 'restore-database',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        // রোল-পারমিশন অ্যাসাইনমেন্ট
        $superAdmin = Role::findByName('super-admin');
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::findByName('admin');
        $admin->givePermissionTo([
            'view-users', 'create-users', 'edit-users',
            'view-tools', 'create-tools', 'edit-tools', 'delete-tools', 'approve-tools', 'reject-tools',
            'view-categories', 'create-categories', 'edit-categories', 'delete-categories',
            'view-reports', 'create-reports', 'download-reports',
            'view-analytics', 'export-analytics',
            'view-settings', 'edit-settings',
            'view-logs', 'clear-cache',
        ]);

        $moderator = Role::findByName('moderator');
        $moderator->givePermissionTo([
            'view-tools', 'approve-tools', 'reject-tools',
            'view-categories', 'edit-categories',
            'view-reports',
        ]);

        $editor = Role::findByName('editor');
        $editor->givePermissionTo([
            'view-tools', 'create-tools', 'edit-tools',
            'view-categories', 'create-categories', 'edit-categories',
        ]);

        $user = Role::findByName('user');
        $user->givePermissionTo([
            'view-profile', 'edit-profile',
            'view-tools', 'create-tools',
            'view-categories',
            'view-automations', 'create-automations', 'edit-automations', 'run-automations',
            'view-reports', 'create-reports', 'download-reports',
            'view-marketplace', 'create-listings', 'edit-listings', 'purchase-tools',
        ]);

        $premiumUser = Role::findByName('premium-user');
        $premiumUser->givePermissionTo($user->permissions->pluck('name')->toArray());

        $developer = Role::findByName('developer');
        $developer->givePermissionTo($user->permissions->pluck('name')->toArray());
        $developer->givePermissionTo(['create-tools', 'edit-tools']);

        $seller = Role::findByName('seller');
        $seller->givePermissionTo($user->permissions->pluck('name')->toArray());
        $seller->givePermissionTo(['sell-tools', 'create-listings', 'edit-listings']);
    }

    /**
     * Create admin user.
     *
     * @return void
     */
    protected function createAdminUser(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@toolkitpro.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@123456'),
                'email_verified_at' => now(),
                'status' => 'active',
                'language' => 'bn',
                'timezone' => 'Asia/Dhaka',
            ]
        );

        $admin->assignRole('super-admin');
    }

    /**
     * Create default settings.
     *
     * @return void
     */
    protected function createDefaultSettings(): void
    {
        $settings = [
            [
                'key' => 'site_name',
                'value' => 'Toolkit Pro',
                'type' => 'string',
                'group' => 'general',
            ],
            [
                'key' => 'site_description',
                'value' => 'বিশ্বের সবচেয়ে সম্পূর্ণ ও শক্তিশালী অনলাইন টুলস প্ল্যাটফর্ম',
                'type' => 'text',
                'group' => 'general',
            ],
            [
                'key' => 'site_logo',
                'value' => null,
                'type' => 'image',
                'group' => 'general',
            ],
            [
                'key' => 'default_language',
                'value' => 'bn',
                'type' => 'string',
                'group' => 'localization',
            ],
            [
                'key' => 'default_currency',
                'value' => 'USD',
                'type' => 'string',
                'group' => 'localization',
            ],
            [
                'key' => 'default_timezone',
                'value' => 'Asia/Dhaka',
                'type' => 'string',
                'group' => 'localization',
            ],
            [
                'key' => 'user_registration_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'users',
            ],
            [
                'key' => 'email_verification_required',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'users',
            ],
            [
                'key' => 'two_factor_authentication',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'security',
            ],
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'system',
            ],
        ];

        foreach ($settings as $setting) {
            \App\Models\Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
