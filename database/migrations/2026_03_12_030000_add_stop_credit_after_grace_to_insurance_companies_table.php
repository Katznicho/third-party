<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_companies', 'stop_credit_after_grace')) {
                $table->boolean('stop_credit_after_grace')
                    ->default(false)
                    ->after('payment_grace_periods');
            }
        });
    }

    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            if (Schema::hasColumn('insurance_companies', 'stop_credit_after_grace')) {
                $table->dropColumn('stop_credit_after_grace');
            }
        });
    }
};

