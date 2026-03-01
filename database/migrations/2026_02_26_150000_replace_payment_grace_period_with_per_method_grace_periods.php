<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Grace period is now per payment method (JSON: { "cash": 0, "mobile_money": 7, ... }).
     */
    public function up(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            if (Schema::hasColumn('insurance_companies', 'payment_grace_period')) {
                $table->dropColumn('payment_grace_period');
            }
            if (!Schema::hasColumn('insurance_companies', 'payment_grace_periods')) {
                $table->json('payment_grace_periods')->nullable()->after('payment_methods')
                    ->comment('Grace period in days per payment method, e.g. {"cash":0,"mobile_money":7}');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            if (Schema::hasColumn('insurance_companies', 'payment_grace_periods')) {
                $table->dropColumn('payment_grace_periods');
            }
            if (!Schema::hasColumn('insurance_companies', 'payment_grace_period')) {
                $table->unsignedInteger('payment_grace_period')->nullable()->after('payment_responsibility_collection');
            }
        });
    }
};
