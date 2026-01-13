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
        Schema::create('payment_responsibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained()->onDelete('cascade');
            $table->foreignId('policy_benefit_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('pre_authorization_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            
            // Responsibility Type
            $table->enum('responsibility_type', [
                'deductible',
                'copayment',
                'coinsurance',
                'shared_payment',
                'out_of_pocket',
                'exclusion',
                'limit_exceeded'
            ]);
            
            // Financial Details
            $table->decimal('total_amount', 15, 2);
            $table->decimal('insurance_pays', 15, 2)->default(0);
            $table->decimal('client_pays', 15, 2)->default(0);
            $table->decimal('other_payer_pays', 15, 2)->default(0); // Third party payer
            
            // Percentage-based Responsibilities
            $table->decimal('client_percentage', 5, 2)->nullable(); // Co-payment percentage
            $table->decimal('insurance_percentage', 5, 2)->nullable();
            
            // Deductible Details
            $table->boolean('is_deductible_applicable')->default(false);
            $table->decimal('deductible_amount', 15, 2)->default(0);
            $table->decimal('deductible_used', 15, 2)->default(0);
            $table->decimal('deductible_remaining', 15, 2)->default(0);
            $table->enum('deductible_type', ['cumulative', 'per_visit', 'annual', 'per_claim'])->nullable();
            
            // Co-payment Details
            $table->boolean('is_copay_applicable')->default(false);
            $table->decimal('copay_amount', 15, 2)->default(0);
            $table->decimal('copay_percentage', 5, 2)->nullable();
            
            // Shared Payment (when applicable)
            $table->decimal('shared_payment_percentage', 5, 2)->nullable();
            $table->text('shared_payment_description')->nullable();
            
            // Status
            $table->enum('status', [
                'pending',
                'calculated',
                'partially_paid',
                'fully_paid',
                'waived',
                'disputed'
            ])->default('pending');
            
            // Payment Tracking
            $table->date('calculation_date');
            $table->date('due_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->text('payment_notes')->nullable();
            
            // Additional Info
            $table->text('description')->nullable();
            $table->text('calculation_notes')->nullable();
            $table->json('calculation_details')->nullable(); // Detailed breakdown
            
            $table->timestamps();
            
            // Indexes
            $table->index(['policy_id', 'status']);
            $table->index(['responsibility_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_responsibilities');
    }
};
