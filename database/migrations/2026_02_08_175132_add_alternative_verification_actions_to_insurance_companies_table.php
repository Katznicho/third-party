<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->enum('email_verification_action', ['auto_accept', 'flag_for_review', 'auto_reject'])->default('auto_accept')->after('enable_email_verification');
            $table->enum('phone_verification_action', ['auto_accept', 'flag_for_review', 'auto_reject'])->default('auto_accept')->after('enable_phone_verification');
            $table->enum('id_passport_verification_action', ['auto_accept', 'flag_for_review', 'auto_reject'])->default('auto_accept')->after('enable_id_passport_verification');
            $table->enum('visit_verification_action', ['auto_accept', 'flag_for_review', 'auto_reject'])->default('auto_accept')->after('enable_visit_verification');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropColumn([
                'email_verification_action',
                'phone_verification_action',
                'id_passport_verification_action',
                'visit_verification_action',
            ]);
        });
    }
};
