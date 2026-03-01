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
            if (!Schema::hasColumn('insurance_companies', 'payment_grace_period')) {
                $table->unsignedInteger('payment_grace_period')->nullable()->after('payment_responsibility_collection')->comment('Grace period in days for payment');
            }
            if (!Schema::hasColumn('insurance_companies', 'payment_methods')) {
                $table->json('payment_methods')->nullable()->after('payment_grace_period')->comment('Allowed payment methods (e.g. bank_transfer, cash, mobile_money)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropColumn(['payment_grace_period', 'payment_methods']);
        });
    }
};
