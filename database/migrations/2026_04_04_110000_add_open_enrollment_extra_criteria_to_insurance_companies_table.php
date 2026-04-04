<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->json('open_enrollment_service_categories')->nullable()->after('generic_policy_id');
            $table->date('open_enrollment_start_date')->nullable()->after('open_enrollment_service_categories');
            $table->date('open_enrollment_end_date')->nullable()->after('open_enrollment_start_date');
            $table->decimal('open_enrollment_max_invoice_amount', 15, 2)->nullable()->after('open_enrollment_end_date');
            $table->json('open_enrollment_nationalities')->nullable()->after('open_enrollment_max_invoice_amount');
            $table->json('open_enrollment_marital_statuses')->nullable()->after('open_enrollment_nationalities');
            $table->json('open_enrollment_client_types')->nullable()->after('open_enrollment_marital_statuses');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropColumn([
                'open_enrollment_service_categories',
                'open_enrollment_start_date',
                'open_enrollment_end_date',
                'open_enrollment_max_invoice_amount',
                'open_enrollment_nationalities',
                'open_enrollment_marital_statuses',
                'open_enrollment_client_types',
            ]);
        });
    }
};
