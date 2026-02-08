<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop table if it exists (from previous failed migration)
        Schema::dropIfExists('pre_authorization_triggers');
        
        Schema::create('pre_authorization_triggers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_company_id');
            
            // Trigger Type
            $table->enum('trigger_type', [
                'high_cost_service',
                'special_procedure',
                'keyword_match',
                'service_category',
                'cost_threshold',
                'custom'
            ]);
            
            // Trigger Configuration
            $table->string('trigger_name');
            $table->text('description')->nullable();
            $table->json('trigger_config')->nullable(); // Flexible config: {"cost_threshold": 100000, "keywords": ["surgery"], "service_category_ids": [1,2]}
            
            // Service Category (optional - if specific to category)
            $table->unsignedBigInteger('service_category_id')->nullable();
            
            // Cost Threshold (for high-cost triggers)
            $table->decimal('cost_threshold', 15, 2)->nullable();
            
            // Keywords (for keyword triggers)
            $table->json('keywords')->nullable(); // Array of keywords to match
            
            // Auto-create pre-authorization settings
            $table->boolean('auto_create_preauth')->default(false);
            $table->boolean('require_manual_approval')->default(true);
            $table->integer('auto_approval_limit')->nullable()->comment('Auto-approve if amount is below this limit');
            
            // Priority
            $table->integer('priority')->default(100);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies')->onDelete('cascade');
            $table->foreign('service_category_id')->references('id')->on('service_categories')->onDelete('set null');
            $table->index(['insurance_company_id', 'is_active', 'priority'], 'preauth_triggers_company_active_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_authorization_triggers');
    }
};
