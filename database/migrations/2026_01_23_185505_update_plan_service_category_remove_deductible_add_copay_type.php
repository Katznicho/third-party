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
        Schema::table('plan_service_category', function (Blueprint $table) {
            // Remove deductible_amount column
            $table->dropColumn('deductible_amount');
            
            // Add copay_type column (fixed or percentage)
            $table->enum('copay_type', ['fixed', 'percentage'])->default('percentage')->after('copay_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_service_category', function (Blueprint $table) {
            // Re-add deductible_amount
            $table->decimal('deductible_amount', 15, 2)->nullable()->default(0)->after('copay_percentage');
            
            // Remove copay_type
            $table->dropColumn('copay_type');
        });
    }
};
