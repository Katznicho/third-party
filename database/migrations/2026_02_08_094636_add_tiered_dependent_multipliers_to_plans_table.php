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
        Schema::table('plans', function (Blueprint $table) {
            // Tiered dependent multipliers (JSON array: [0.50, 0.40, 0.35] for tiers 1, 2, 3)
            $table->json('dependent_multiplier_tiers')->nullable()->after('dependent_coverage_multiplier');
            // Floor limit for dependents beyond defined tiers
            $table->decimal('dependent_multiplier_floor', 5, 2)->nullable()->after('dependent_multiplier_tiers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['dependent_multiplier_tiers', 'dependent_multiplier_floor']);
        });
    }
};
