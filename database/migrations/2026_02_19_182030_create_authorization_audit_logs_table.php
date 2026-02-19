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
            $table->json('context_data')->nullable(); // Policy info, client info, service category, etc.
            $table->json('rule_evaluation_results')->nullable(); // Which rules matched, which didn't
            
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authorization_audit_logs');
    }
};
