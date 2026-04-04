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
        Schema::table('clients', function (Blueprint $table) {
            // Link client to insurance company for open enrollment tracking
            $table->foreignId('insurance_company_id')
                ->nullable()
                ->after('registered_via_open_enrollment')
                ->constrained()
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\InsuranceCompany::class);
            $table->dropColumn('insurance_company_id');
        });
    }
};
