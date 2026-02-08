<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->boolean('enable_name_dob_verification')->default(false)->after('required_client_fields');
            $table->boolean('enable_id_passport_verification')->default(false);
            $table->boolean('enable_phone_verification')->default(false);
            $table->boolean('enable_email_verification')->default(false);
            $table->enum('name_mismatch_action', ['auto_reject', 'flag_for_review'])->default('flag_for_review');
            $table->enum('dob_mismatch_action', ['auto_reject', 'flag_for_review'])->default('flag_for_review');
            $table->enum('id_mismatch_action', ['auto_reject', 'flag_for_review'])->default('flag_for_review');
            $table->integer('name_similarity_threshold')->default(80);
            $table->integer('dob_tolerance_days')->default(0);
            $table->boolean('enable_visit_verification')->default(false);
            $table->integer('visit_verification_validity_days')->default(30);
        });
    }

    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropColumn([
                'enable_name_dob_verification',
                'enable_id_passport_verification',
                'enable_phone_verification',
                'enable_email_verification',
                'name_mismatch_action',
                'dob_mismatch_action',
                'id_mismatch_action',
                'name_similarity_threshold',
                'dob_tolerance_days',
                'enable_visit_verification',
                'visit_verification_validity_days',
            ]);
        });
    }
};
