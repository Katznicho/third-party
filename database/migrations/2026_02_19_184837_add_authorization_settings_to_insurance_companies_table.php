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
            // Simple authorization settings
            $table->boolean('enable_auto_authorization')->default(true)->after('email_otp_expiry_minutes');
            $table->decimal('auto_approve_max_amount', 15, 2)->nullable()->after('enable_auto_authorization');
            $table->decimal('auto_reject_min_amount', 15, 2)->nullable()->after('auto_approve_max_amount');
            $table->boolean('require_manual_review_above_amount')->default(true)->after('auto_reject_min_amount');
            $table->decimal('manual_review_threshold_amount', 15, 2)->nullable()->after('require_manual_review_above_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropColumn([
                'enable_auto_authorization',
                'auto_approve_max_amount',
                'auto_reject_min_amount',
                'require_manual_review_above_amount',
                'manual_review_threshold_amount',
            ]);
        });
    }
};
