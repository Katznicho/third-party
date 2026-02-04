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
            // Add base_amount column (what the client pays)
            if (!Schema::hasColumn('plan_service_category', 'base_amount')) {
                $table->decimal('base_amount', 15, 2)->nullable()->after('benefit_amount');
            }
            
            // Remove copay columns if they exist
            if (Schema::hasColumn('plan_service_category', 'copay_percentage')) {
                $table->dropColumn('copay_percentage');
            }
            if (Schema::hasColumn('plan_service_category', 'copay_type')) {
                $table->dropColumn('copay_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_service_category', function (Blueprint $table) {
            // Re-add copay columns
            if (!Schema::hasColumn('plan_service_category', 'copay_percentage')) {
                $table->decimal('copay_percentage', 5, 2)->nullable()->default(0)->after('benefit_amount');
            }
            if (!Schema::hasColumn('plan_service_category', 'copay_type')) {
                $table->enum('copay_type', ['fixed', 'percentage'])->default('percentage')->after('copay_percentage');
            }
            
            // Remove base_amount
            if (Schema::hasColumn('plan_service_category', 'base_amount')) {
                $table->dropColumn('base_amount');
            }
        });
    }
};
