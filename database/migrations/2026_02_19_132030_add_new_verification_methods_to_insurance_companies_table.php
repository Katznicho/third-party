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
            // New verification method fields
            $table->boolean('require_physical_id')->default(true)->after('required_client_fields');
            $table->boolean('enable_method_1')->default(true)->after('require_physical_id');
            $table->boolean('enable_method_2')->default(false)->after('enable_method_1');
            $table->boolean('enable_method_3')->default(false)->after('enable_method_2');
            $table->boolean('enable_method_4')->default(false)->after('enable_method_3');
            $table->integer('phone_otp_expiry_minutes')->default(10)->after('dob_tolerance_days');
            $table->integer('email_otp_expiry_minutes')->default(15)->after('phone_otp_expiry_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropColumn([
                'require_physical_id',
                'enable_method_1',
                'enable_method_2',
                'enable_method_3',
                'enable_method_4',
                'phone_otp_expiry_minutes',
                'email_otp_expiry_minutes',
            ]);
        });
    }
};
