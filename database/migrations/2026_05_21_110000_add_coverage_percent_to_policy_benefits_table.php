<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policy_benefits', function (Blueprint $table) {
            if (! Schema::hasColumn('policy_benefits', 'coverage_percent')) {
                $table->decimal('coverage_percent', 5, 2)->default(100.00)->after('benefit_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('policy_benefits', function (Blueprint $table) {
            if (Schema::hasColumn('policy_benefits', 'coverage_percent')) {
                $table->dropColumn('coverage_percent');
            }
        });
    }
};
