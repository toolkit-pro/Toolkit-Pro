<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->string('phone', 20)->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();
            
            // Profile Information
            $table->string('avatar', 255)->nullable();
            $table->text('bio')->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            
            // Preferences
            $table->string('language', 5)->default('bn');
            $table->string('timezone', 50)->default('Asia/Dhaka');
            $table->string('currency', 10)->default('USD');
            $table->json('preferences')->nullable();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'suspended', 'banned'])->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            
            // Two-Factor Authentication
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            
            // Remember Token
            $table->rememberToken();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('status');
            $table->index('language');
            $table->index('country');
            $table->index('last_login_at');
            $table->index(['status', 'created_at']);
            $table->index(['email', 'status']);
            
            // Full-text search
            $table->fullText(['name', 'email', 'bio']);
        });

        // Table comment
        DB::statement('COMMENT ON TABLE users IS \'Users table for Toolkit Pro platform\'');
        
        // Column comments
        DB::statement('COMMENT ON COLUMN users.preferences IS \'User preferences in JSON format\'');
        DB::statement('COMMENT ON COLUMN users.metadata IS \'Additional user metadata in JSON format\'');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
