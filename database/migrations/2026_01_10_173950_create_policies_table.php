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
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_number')->unique();
            $table->unsignedBigInteger('insurance_company_id');
            $table->unsignedBigInteger('principal_member_id');
            
            // Policy Plan Type
            $table->enum('plan_type', [
                'Prestige',
                'Executive',
                'Standard Plus',
                'Standard',
                'Regular',
                'Budget'
            ]);
            
            // Policy Dates
            $table->date('inception_date');
            $table->date('expiry_date');
            $table->date('desired_start_date')->nullable();
            
            // Premium Information
            $table->decimal('total_premium', 15, 2);
            $table->decimal('insurance_training_levy', 15, 2)->default(0); // 0.5%
            $table->decimal('stamp_duty', 15, 2)->default(35000);
            $table->decimal('total_premium_due', 15, 2);
            
            // Agent/Broker
            $table->string('agent_broker_name')->nullable();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'suspended', 'expired', 'cancelled'])->default('active');
            $table->boolean('is_paid')->default(false);
            $table->date('payment_date')->nullable();
            
            // Additional Options
            $table->boolean('has_deductible')->default(false);
            $table->boolean('telemedicine_only')->default(false);
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        
        // Add foreign key constraints
        Schema::table('policies', function (Blueprint $table) {
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies')->onDelete('cascade');
            $table->foreign('principal_member_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
