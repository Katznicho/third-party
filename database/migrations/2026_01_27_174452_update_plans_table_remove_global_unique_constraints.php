<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing unique constraints on code and slug
        Schema::table('plans', function (Blueprint $table) {
            // Check if unique index exists before dropping
            $indexes = DB::select("SHOW INDEX FROM plans WHERE Key_name = 'plans_code_unique'");
            if (!empty($indexes)) {
                $table->dropUnique('plans_code_unique');
            }
            
            $indexes = DB::select("SHOW INDEX FROM plans WHERE Key_name = 'plans_slug_unique'");
            if (!empty($indexes)) {
                $table->dropUnique('plans_slug_unique');
            }
        });
        
        // Add composite unique constraint: code must be unique per insurance company
        Schema::table('plans', function (Blueprint $table) {
            $table->unique(['code', 'insurance_company_id'], 'plans_code_insurance_company_unique');
            // Slug can also be unique per insurance company for better SEO
            $table->unique(['slug', 'insurance_company_id'], 'plans_slug_insurance_company_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Drop composite unique constraints
            $table->dropUnique('plans_code_insurance_company_unique');
            $table->dropUnique('plans_slug_insurance_company_unique');
            
            // Restore global unique constraints
            $table->unique('code');
            $table->unique('slug');
        });
    }
};
