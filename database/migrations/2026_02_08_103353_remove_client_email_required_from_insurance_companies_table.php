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
            if (Schema::hasColumn('insurance_companies', 'client_email_required')) {
                $table->dropColumn('client_email_required');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_companies', 'client_email_required')) {
                $table->boolean('client_email_required')->default(false)->after('policy_number_company_code_length');
            }
        });
    }
};
