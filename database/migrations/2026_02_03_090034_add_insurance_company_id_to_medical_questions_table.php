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
            // Add insurance_company_id column if it doesn't exist
            if (!Schema::hasColumn('medical_questions', 'insurance_company_id')) {
                $table->unsignedBigInteger('insurance_company_id')->nullable()->after('id');
                $table->foreign('insurance_company_id')->references('id')->on('insurance_companies')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_questions', function (Blueprint $table) {
            if (Schema::hasColumn('medical_questions', 'insurance_company_id')) {
                $table->dropForeign(['insurance_company_id']);
                $table->dropColumn('insurance_company_id');
            }
        });
    }
};
