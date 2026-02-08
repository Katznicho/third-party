<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop table if it exists (from previous failed migration)
        Schema::dropIfExists('coverage_decision_matrix');
        
        Schema::create('coverage_decision_matrix', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_company_id');
            
            // Rule Conditions
            $table->string('rule_name');
            $table->text('description')->nullable();
            $table->enum('condition_type', [
                'service_category_not_covered',
                'service_category_coverage_limit_exceeded',
                'cost_threshold_exceeded',
                'keyword_match',
                'procedure_type',
                'visit_type_not_covered',
                'custom_condition'
            ]);
            
            // Condition Details (JSON for flexibility)
            $table->json('condition_config')->nullable(); // e.g., {"service_category_ids": [1,2], "keywords": ["surgery", "operation"]}
            
            // Decision
            $table->enum('action', ['auto_reject', 'flag_for_review', 'require_pre_authorization'])->default('flag_for_review');
            $table->text('rejection_message')->nullable();
            $table->text('review_notes_template')->nullable();
            
            // Priority (lower number = higher priority)
            $table->integer('priority')->default(100);
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies')->onDelete('cascade');
            $table->index(['insurance_company_id', 'is_active', 'priority'], 'coverage_matrix_company_active_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coverage_decision_matrix');
    }
};
