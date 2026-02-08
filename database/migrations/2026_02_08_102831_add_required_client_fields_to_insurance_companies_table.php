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
            if (!Schema::hasColumn('insurance_companies', 'required_client_fields')) {
                $table->json('required_client_fields')->nullable()->after('coinsurance_contributes_to_deductible');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            if (Schema::hasColumn('insurance_companies', 'required_client_fields')) {
                $table->dropColumn('required_client_fields');
            }
        });
    }
};
