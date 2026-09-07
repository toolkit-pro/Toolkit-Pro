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
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            
            // Media
            $table->string('icon', 255)->nullable();
            $table->string('thumbnail', 255)->nullable();
            $table->string('screenshot', 255)->nullable();
            $table->string('video_url', 255)->nullable();
            
            // URLs
            $table->string('url', 255)->nullable();
            $table->string('api_url', 255)->nullable();
            $table->string('documentation_url', 255)->nullable();
            $table->string('support_url', 255)->nullable();
            
            // Version & Status
            $table->string('version', 20)->default('1.0.0');
            $table->enum('status', ['active', 'inactive', 'pending', 'rejected', 'archived'])->default('active');
            
            // Flags
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_free')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_approved')->default(false);
            
            // Pricing
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('billing_cycle', 20)->nullable(); // one_time, monthly, yearly
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->timestamp('discount_ends_at')->nullable();
            
            // Relationships
            $table->foreignId('creator_id')->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->onUpdate('cascade');
            
            // Statistics
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('uses_count')->default(0);
            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->unsignedBigInteger('favorites_count')->default(0);
            $table->unsignedBigInteger('shares_count')->default(0);
            $table->unsignedBigInteger('comments_count')->default(0);
            
            // Rating
            $table->decimal('rating', 5, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            
            // Performance
            $table->unsignedInteger('average_time_spent')->default(0); // in seconds
            $table->unsignedInteger('success_rate')->default(0); // percentage
            $table->unsignedInteger('error_rate')->default(0); // percentage
            
            // Metadata
            $table->json('tags')->nullable();
            $table->json('requirements')->nullable();
            $table->json('compatibility')->nullable();
            $table->json('languages')->nullable();
            $table->json('metadata')->nullable();
            
            // SEO
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords', 255)->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('status');
            $table->index('is_featured');
            $table->index('is_premium');
            $table->index('is_free');
            $table->index('is_verified');
            $table->index('is_approved');
            $table->index('price');
            $table->index('rating');
            $table->index('views_count');
            $table->index('uses_count');
            $table->index('downloads_count');
            $table->index('favorites_count');
            $table->index('creator_id');
            $table->index(['status', 'is_featured']);
            $table->index(['status', 'is_premium']);
            $table->index(['status', 'is_free']);
            $table->index(['status', 'created_at']);
            $table->index(['status', 'rating']);
            $table->index(['status', 'uses_count']);
            $table->index(['status', 'views_count']);
            
            // Full-text search
            $table->fullText(['name', 'description', 'instructions']);
            $table->fullText(['meta_title', 'meta_description', 'meta_keywords']);
            
            // Unique constraints
            $table->unique(['creator_id', 'slug']);
        });

        // Table comment
        DB::statement('COMMENT ON TABLE tools IS \'Tools table for Toolkit Pro platform\'');
        
        // Column comments
        DB::statement('COMMENT ON COLUMN tools.tags IS \'Tool tags in JSON array format\'');
        DB::statement('COMMENT ON COLUMN tools.requirements IS \'Tool requirements in JSON format\'');
        DB::statement('COMMENT ON COLUMN tools.compatibility IS \'Tool compatibility information in JSON format\'');
        DB::statement('COMMENT ON COLUMN tools.languages IS \'Supported languages in JSON array format\'');
        DB::statement('COMMENT ON COLUMN tools.metadata IS \'Additional tool metadata in JSON format\'');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};
