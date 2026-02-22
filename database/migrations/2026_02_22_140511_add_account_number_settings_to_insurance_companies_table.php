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
            // Account number generation format
            // Format examples:
            // {COMPANY}{YEAR}{RANDOM} = AAR20261234567890 (12 digits total)
            // {YEAR}{RANDOM} = 20261234567890 (12 digits total)
            // {RANDOM} = 123456789012 (12 digits)
            $table->string('account_number_format')->nullable()->after('policy_number_company_code_length')->default('{COMPANY}{YEAR}{RANDOM}');
            
            // Random part length (will be calculated to ensure 12 digits total)
            $table->integer('account_number_random_length')->default(6)->after('account_number_format');
            
            // Random part type: numeric, alphanumeric, alphabetic
            $table->enum('account_number_random_type', ['numeric', 'alphanumeric', 'alphabetic'])->default('numeric')->after('account_number_random_length');
            
            // Company code prefix length (how many characters from company code to use)
            $table->integer('account_number_company_code_length')->default(3)->after('account_number_random_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropColumn([
                'account_number_format',
                'account_number_random_length',
                'account_number_random_type',
                'account_number_company_code_length',
            ]);
        });
    }
};
