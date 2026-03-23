<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_companies', 'country_name')) {
                $table->string('country_name', 120)->nullable()->after('phone');
            }

            if (!Schema::hasColumn('insurance_companies', 'currency_code')) {
                $table->string('currency_code', 10)->default('UGX')->after('country_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            if (Schema::hasColumn('insurance_companies', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
            if (Schema::hasColumn('insurance_companies', 'country_name')) {
                $table->dropColumn('country_name');
            }
        });
    }
};

