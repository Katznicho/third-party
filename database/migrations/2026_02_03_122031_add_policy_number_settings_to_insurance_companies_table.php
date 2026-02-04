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
            // Policy number generation format
            // Format examples:
            // {COMPANY}-{YEAR}{MONTH}{DAY}-{RANDOM} = AAR-20260123-ABC123
            // {YEAR}-{RANDOM} = 2026-ABC123
            // {COMPANY}{YEAR}{MONTH}{DAY}{RANDOM} = AAR20260123ABC123
            $table->string('policy_number_format')->nullable()->after('is_active')->default('{COMPANY}-{YEAR}{MONTH}{DAY}-{RANDOM}');
            
            // Random part length (default 6 characters)
            $table->integer('policy_number_random_length')->default(6)->after('policy_number_format');
            
            // Random part type: alphanumeric, numeric, alphabetic
            $table->enum('policy_number_random_type', ['alphanumeric', 'numeric', 'alphabetic'])->default('alphanumeric')->after('policy_number_random_length');
            
            // Company code prefix length (how many characters from company code to use)
            $table->integer('policy_number_company_code_length')->default(3)->after('policy_number_random_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropColumn([
                'policy_number_format',
                'policy_number_random_length',
                'policy_number_random_type',
                'policy_number_company_code_length',
            ]);
        });
    }
};
