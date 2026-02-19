<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('authorization_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_company_id')->constrained()->onDelete('cascade');
            
            // Rule Information
            $table->string('rule_name');
            $table->text('description')->nullable();
            
            // Rule Type
            $table->enum('rule_type', [
                'amount',
                'service_category',
                'policy_type',
                'client_tier',
                'time_based',
                'risk_based',
                'combined' // Multiple conditions
            ]);
            
            // Conditions (JSON - flexible structure)
            $table->json('conditions');
            
            // Action to take
            $table->enum('action', [
                'auto_approve',
                'auto_reject',
                'flag_for_review',
                'partially_approve'
            ]);
            
            // For partially_approve action
            $table->decimal('partial_approval_percentage', 5, 2)->nullable(); // e.g., 50.00 for 50%
            $table->decimal('partial_approval_amount', 15, 2)->nullable(); // Fixed amount
            
            // Priority (lower number = higher priority)
            $table->integer('priority')->default(100);
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Metadata
            $table->json('metadata')->nullable(); // Additional flexible data
            
            $table->timestamps();
            
            // Indexes (with shorter names to avoid MySQL 64 char limit)
            $table->index(['insurance_company_id', 'is_active', 'priority'], 'auth_rules_company_active_priority_idx');
            $table->index(['rule_type', 'is_active'], 'auth_rules_type_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authorization_rules');
    }
};
