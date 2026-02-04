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
        Schema::table('plans', function (Blueprint $table) {
            // Age Limits
            $table->integer('min_enrollment_age')->nullable()->after('sort_order');
            $table->integer('max_enrollment_age')->nullable()->after('min_enrollment_age');
            
            // Effective Dates
            $table->date('effective_start_date')->nullable()->after('max_enrollment_age');
            $table->date('effective_end_date')->nullable()->after('effective_start_date');
            
            // Dependent Coverage Settings
            $table->decimal('dependent_coverage_multiplier', 5, 2)->default(0.50)->after('effective_end_date');
            
            // Coverage Limits
            $table->decimal('annual_max_coverage', 15, 2)->nullable()->after('dependent_coverage_multiplier');
            $table->decimal('lifetime_max_coverage', 15, 2)->nullable()->after('annual_max_coverage');
            $table->decimal('per_incident_max_coverage', 15, 2)->nullable()->after('lifetime_max_coverage');
            
            // Plan Image/Icon
            $table->string('image_path')->nullable()->after('per_incident_max_coverage');
            
            // Terms & Conditions
            $table->text('terms_and_conditions')->nullable()->after('image_path');
            $table->string('terms_link')->nullable()->after('terms_and_conditions');
            
            // Premium Calculation Settings
            $table->decimal('base_premium', 15, 2)->nullable()->after('terms_link');
            $table->enum('premium_calculation_method', ['benefit_based', 'fixed', 'hybrid'])->default('benefit_based')->after('base_premium');
            $table->decimal('insurance_training_levy_percentage', 5, 2)->default(0.50)->after('premium_calculation_method');
            $table->decimal('stamp_duty_amount', 15, 2)->default(35000)->after('insurance_training_levy_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'min_enrollment_age',
                'max_enrollment_age',
                'effective_start_date',
                'effective_end_date',
                'dependent_coverage_multiplier',
                'annual_max_coverage',
                'lifetime_max_coverage',
                'per_incident_max_coverage',
                'image_path',
                'terms_and_conditions',
                'terms_link',
                'base_premium',
                'premium_calculation_method',
                'insurance_training_levy_percentage',
                'stamp_duty_amount',
            ]);
        });
    }
};
