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
        Schema::table('medical_questions', function (Blueprint $table) {
            // Monetary Impact Fields
            $table->boolean('has_monetary_impact')->default(false)->after('is_active');
            $table->enum('monetary_impact_type', ['premium_adjustment', 'deductible_adjustment', 'coverage_limit_adjustment', 'none'])->default('none')->after('has_monetary_impact');
            $table->decimal('monetary_impact_amount', 15, 2)->nullable()->after('monetary_impact_type');
            $table->boolean('monetary_impact_is_percentage')->default(false)->after('monetary_impact_amount');
            $table->string('monetary_impact_applies_to_response')->nullable()->after('monetary_impact_is_percentage');
            $table->text('monetary_impact_description')->nullable()->after('monetary_impact_applies_to_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_questions', function (Blueprint $table) {
            $table->dropColumn([
                'has_monetary_impact',
                'monetary_impact_type',
                'monetary_impact_amount',
                'monetary_impact_is_percentage',
                'monetary_impact_applies_to_response',
                'monetary_impact_description',
            ]);
        });
    }
};
