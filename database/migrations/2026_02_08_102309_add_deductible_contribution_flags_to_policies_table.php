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
        Schema::table('policies', function (Blueprint $table) {
            if (!Schema::hasColumn('policies', 'copay_contributes_to_deductible')) {
                $table->boolean('copay_contributes_to_deductible')->nullable()->after('copay_max_limit');
            }
            if (!Schema::hasColumn('policies', 'coinsurance_contributes_to_deductible')) {
                $table->boolean('coinsurance_contributes_to_deductible')->nullable()->after('copay_contributes_to_deductible');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            if (Schema::hasColumn('policies', 'copay_contributes_to_deductible')) {
                $table->dropColumn('copay_contributes_to_deductible');
            }
            if (Schema::hasColumn('policies', 'coinsurance_contributes_to_deductible')) {
                $table->dropColumn('coinsurance_contributes_to_deductible');
            }
        });
    }
};
