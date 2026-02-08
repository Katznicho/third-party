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
            if (Schema::hasColumn('plans', 'effective_start_date')) {
                $table->dropColumn('effective_start_date');
            }
            if (Schema::hasColumn('plans', 'effective_end_date')) {
                $table->dropColumn('effective_end_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->date('effective_start_date')->nullable()->after('max_enrollment_age');
            $table->date('effective_end_date')->nullable()->after('effective_start_date');
        });
    }
};
