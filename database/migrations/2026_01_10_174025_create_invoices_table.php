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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('policy_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            
            // Invoice Type
            $table->enum('invoice_type', [
                'premium',
                'claim',
                'service',
                'adjustment',
                'refund'
            ])->default('premium');
            
            // Invoice Details
            $table->text('description')->nullable();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            
            // Financial Information
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2); // Total - Paid
            
            // Status
            $table->enum('status', [
                'draft',
                'sent',
                'paid',
                'partial',
                'overdue',
                'cancelled',
                'refunded'
            ])->default('draft');
            
            // Billing Period (for premium invoices)
            $table->date('billing_period_start')->nullable();
            $table->date('billing_period_end')->nullable();
            
            // Premium Breakdown (for premium invoices)
            $table->decimal('premium_amount', 15, 2)->default(0);
            $table->decimal('insurance_training_levy', 15, 2)->default(0);
            $table->decimal('stamp_duty', 15, 2)->default(0);
            
            // Payment Terms
            $table->integer('payment_terms_days')->default(30);
            $table->text('payment_instructions')->nullable();
            $table->text('notes')->nullable();
            
            // PDF Generation
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();
            
            // Additional Info
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('line_items')->nullable(); // JSON array of invoice items
            
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'due_date']);
            $table->index(['policy_id', 'invoice_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
