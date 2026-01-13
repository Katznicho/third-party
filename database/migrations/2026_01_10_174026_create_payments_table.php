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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_reference')->unique();
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('policy_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            
            // Payment Type
            $table->enum('payment_type', [
                'premium_payment',
                'claim_settlement',
                'refund',
                'adjustment',
                'partial_payment',
                'full_payment'
            ]);
            
            // Payment Details
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2); // Amount - Paid
            
            // Payment Method
            $table->enum('payment_method', [
                'cash',
                'bank_transfer',
                'mobile_money',
                'cheque',
                'card',
                'credit',
                'other'
            ]);
            
            // Payment Information
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('mobile_money_provider')->nullable(); // MTN, Airtel, etc.
            $table->string('mobile_money_number')->nullable();
            $table->string('transaction_id')->nullable(); // Bank/Mobile money transaction ID
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            
            // Status
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
                'refunded',
                'reversed'
            ])->default('pending');
            
            // Dates
            $table->date('payment_date');
            $table->date('received_date')->nullable();
            $table->date('cleared_date')->nullable();
            $table->timestamp('processed_at')->nullable();
            
            // Receipt Information
            $table->string('receipt_number')->nullable()->unique();
            $table->string('receipt_path')->nullable(); // PDF receipt path
            $table->timestamp('receipt_generated_at')->nullable();
            
            // Additional Info
            $table->text('payment_notes')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('payment_metadata')->nullable(); // Additional payment data
            
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'payment_date']);
            $table->index(['policy_id', 'payment_type']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
