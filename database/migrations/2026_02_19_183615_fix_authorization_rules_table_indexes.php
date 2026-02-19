<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop foreign keys first if they exist
        if (Schema::hasTable('pre_authorizations')) {
            try {
                // Get the actual foreign key constraint name from MySQL
                $foreignKeyName = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'pre_authorizations' 
                    AND COLUMN_NAME = 'authorization_rule_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (!empty($foreignKeyName)) {
                    $constraintName = $foreignKeyName[0]->CONSTRAINT_NAME;
                    DB::statement("ALTER TABLE `pre_authorizations` DROP FOREIGN KEY `{$constraintName}`");
                }
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }
        }
        
        if (Schema::hasTable('authorization_audit_logs')) {
            try {
                $foreignKeyName = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'authorization_audit_logs' 
                    AND COLUMN_NAME = 'authorization_rule_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (!empty($foreignKeyName)) {
                    $constraintName = $foreignKeyName[0]->CONSTRAINT_NAME;
                    DB::statement("ALTER TABLE `authorization_audit_logs` DROP FOREIGN KEY `{$constraintName}`");
                }
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }
            Schema::dropIfExists('authorization_audit_logs');
        }
        
        // Drop the tables if they exist (from failed migration)
        Schema::dropIfExists('authorization_rules');
        
        // Now create them properly with correct index names
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
                'combined'
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
            $table->decimal('partial_approval_percentage', 5, 2)->nullable();
            $table->decimal('partial_approval_amount', 15, 2)->nullable();
            
            // Priority (lower number = higher priority)
            $table->integer('priority')->default(100);
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes (with shorter names to avoid MySQL 64 char limit)
            $table->index(['insurance_company_id', 'is_active', 'priority'], 'auth_rules_company_active_priority_idx');
            $table->index(['rule_type', 'is_active'], 'auth_rules_type_active_idx');
        });

        Schema::create('authorization_audit_logs', function (Blueprint $table) {
            $table->id();
            
            // Related entities
            $table->foreignId('pre_authorization_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('authorization_rule_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('insurance_company_id')->constrained()->onDelete('cascade');
            
            // Decision information
            $table->enum('decision', [
                'auto_approved',
                'auto_rejected',
                'flagged_for_review',
                'manually_approved',
                'manually_rejected',
                'partially_approved'
            ]);
            
            $table->enum('authorization_method', [
                'automatic',
                'manual'
            ]);
            
            // Amounts
            $table->decimal('requested_amount', 15, 2);
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->decimal('rejected_amount', 15, 2)->nullable();
            
            // Context
            $table->json('context_data')->nullable();
            $table->json('rule_evaluation_results')->nullable();
            
            // User information
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Timestamps
            $table->timestamp('processed_at');
            
            $table->timestamps();
            
            // Indexes (with shorter names to avoid MySQL 64 char limit)
            $table->index(['pre_authorization_id'], 'auth_logs_preauth_idx');
            $table->index(['invoice_id'], 'auth_logs_invoice_idx');
            $table->index(['insurance_company_id', 'processed_at'], 'auth_logs_company_processed_idx');
            $table->index(['decision', 'authorization_method'], 'auth_logs_decision_method_idx');
        });
        
        // Re-add the foreign key to pre_authorizations if the column exists
        if (Schema::hasColumn('pre_authorizations', 'authorization_rule_id')) {
            Schema::table('pre_authorizations', function (Blueprint $table) {
                $table->foreign('authorization_rule_id')->references('id')->on('authorization_rules')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authorization_audit_logs');
        Schema::dropIfExists('authorization_rules');
    }
};
