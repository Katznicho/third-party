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
        Schema::create('client_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('insurance_company_id')->constrained()->onDelete('cascade');
            $table->string('account_number')->unique();
            $table->enum('account_type', ['individual', 'corporate', 'group'])->default('individual');
            $table->enum('status', ['active', 'inactive', 'suspended', 'closed'])->default('active');
            
            // Account Balance Information
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->decimal('total_debits', 15, 2)->default(0);
            $table->decimal('total_credits', 15, 2)->default(0);
            $table->decimal('available_balance', 15, 2)->default(0);
            
            // Account Dates
            $table->date('opened_date');
            $table->date('last_transaction_date')->nullable();
            $table->date('last_statement_date')->nullable();
            
            // Account Settings
            $table->boolean('auto_generate_statements')->default(true);
            $table->enum('statement_frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'on_demand'])->default('monthly');
            
            // Additional Information
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['insurance_company_id', 'status']);
            $table->index('account_number');
            $table->index('opened_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_accounts');
    }
};
