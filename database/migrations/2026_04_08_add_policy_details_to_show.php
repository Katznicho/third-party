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
        Schema::table('insurance_companies', function (Blueprint $table) {
            // JSON column to store which policy details should be displayed
            // Default: policy_number, deductible_amount, copay_amount, coinsurance_percentage
            $table->json('policy_details_to_display_at_registration')->nullable()->after('show_policy_details_at_registration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropColumn('policy_details_to_display_at_registration');
        });
    }
};
