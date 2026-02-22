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
            // Add columns only if they don't exist
            if (!Schema::hasColumn('insurance_companies', 'require_physical_id')) {
                $table->boolean('require_physical_id')->default(true)->after('required_client_fields');
            }
            if (!Schema::hasColumn('insurance_companies', 'enable_method_1')) {
                $table->boolean('enable_method_1')->default(true)->after('require_physical_id');
            }
            if (!Schema::hasColumn('insurance_companies', 'enable_method_2')) {
                $table->boolean('enable_method_2')->default(false)->after('enable_method_1');
            }
            if (!Schema::hasColumn('insurance_companies', 'enable_method_3')) {
                $table->boolean('enable_method_3')->default(false)->after('enable_method_2');
            }
            if (!Schema::hasColumn('insurance_companies', 'enable_method_4')) {
                $table->boolean('enable_method_4')->default(false)->after('enable_method_3');
            }
            if (!Schema::hasColumn('insurance_companies', 'phone_otp_expiry_minutes')) {
                // Find the position after dob_tolerance_days
                $table->integer('phone_otp_expiry_minutes')->default(10)->after('dob_tolerance_days');
            }
            if (!Schema::hasColumn('insurance_companies', 'email_otp_expiry_minutes')) {
                $table->integer('email_otp_expiry_minutes')->default(15)->after('phone_otp_expiry_minutes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $columns = [
                'require_physical_id',
                'enable_method_1',
                'enable_method_2',
                'enable_method_3',
                'enable_method_4',
                'phone_otp_expiry_minutes',
                'email_otp_expiry_minutes',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('insurance_companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
