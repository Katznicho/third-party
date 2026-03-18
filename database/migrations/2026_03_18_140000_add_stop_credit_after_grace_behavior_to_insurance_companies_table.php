<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_companies', 'stop_credit_after_grace_behavior')) {
                $table->string('stop_credit_after_grace_behavior', 32)
                    ->nullable()
                    ->after('stop_credit_after_grace');
            }
        });
    }

    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            if (Schema::hasColumn('insurance_companies', 'stop_credit_after_grace_behavior')) {
                $table->dropColumn('stop_credit_after_grace_behavior');
            }
        });
    }
};

