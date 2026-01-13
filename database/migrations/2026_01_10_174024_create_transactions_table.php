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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->foreignId('policy_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('pre_authorization_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            
            // Transaction Type
            $table->enum('type', [
                'premium_payment',
                'claim_payment',
                'refund',
                'adjustment',
                'deductible',
                'copayment',
                'service_charge',
                'penalty',
                'credit',
                'debit'
            ]);
            
            // Transaction Details
            $table->string('reference_number')->nullable();
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->enum('transaction_status', [
                'pending',
                'cleared',
                'cancelled',
                'disputed',
                'reversed'
            ])->default('pending');
            
            // Financial Details
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->decimal('balance_before', 15, 2)->nullable();
            $table->decimal('balance_after', 15, 2)->nullable();
            
            // Service Category (for claim-related transactions)
            $table->foreignId('service_category_id')->nullable()->constrained()->onDelete('set null');
            
            // Payment Method
            $table->enum('payment_method', [
                'cash',
                'bank_transfer',
                'mobile_money',
                'cheque',
                'card',
                'credit',
                'other'
            ])->nullable();
            
            // Dates
            $table->date('transaction_date');
            $table->date('cleared_date')->nullable();
            $table->date('due_date')->nullable();
            
            // Additional Info
            $table->string('cleared_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional structured data
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['transaction_status', 'transaction_date']);
            $table->index(['policy_id', 'transaction_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
