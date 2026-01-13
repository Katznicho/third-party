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
        Schema::create('policy_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_category_id')->constrained()->onDelete('cascade');
            
            // Financial Limits
            $table->decimal('benefit_amount', 15, 2)->default(0); // UGX amount
            $table->decimal('used_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            
            // Hospital Cash (per day)
            $table->decimal('hospital_cash_per_day', 15, 2)->nullable();
            $table->integer('hospital_cash_max_days')->default(30); // Up to 30 days per year
            
            // Life Cover
            $table->decimal('life_cover_amount', 15, 2)->nullable(); // Sum Assured
            
            // Allowed Services (JSON array of service codes/names)
            $table->json('allowed_services')->nullable();
            
            // Payment Responsibilities
            $table->decimal('copay_percentage', 5, 2)->default(0); // Co-payment percentage
            $table->decimal('deductible_amount', 15, 2)->default(0);
            $table->enum('deductible_type', ['cumulative', 'per_visit', 'annual'])->nullable();
            
            // Shared Payment Info
            $table->decimal('shared_payment_percentage', 5, 2)->default(0); // Percentage shared between client and insurance
            $table->text('payment_notes')->nullable();
            
            // Status
            $table->boolean('is_enabled')->default(true);
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            
            $table->timestamps();
            
            // Unique constraint: one benefit per policy per service category
            $table->unique(['policy_id', 'service_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_benefits');
    }
};
