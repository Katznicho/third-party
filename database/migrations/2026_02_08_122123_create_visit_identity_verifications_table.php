<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_identity_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_company_id');
            $table->string('visit_id')->unique()->comment('Unique visit identifier from Kashtre');
            $table->unsignedBigInteger('policy_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            
            // Verification data
            $table->string('provided_name')->nullable();
            $table->date('provided_date_of_birth')->nullable();
            $table->string('provided_id_passport_no')->nullable();
            $table->string('provided_phone')->nullable();
            $table->string('provided_email')->nullable();
            
            // Matched data from policy/client
            $table->string('matched_name')->nullable();
            $table->date('matched_date_of_birth')->nullable();
            $table->string('matched_id_passport_no')->nullable();
            $table->string('matched_phone')->nullable();
            $table->string('matched_email')->nullable();
            
            // Verification results
            $table->enum('verification_status', ['pending', 'verified', 'rejected', 'flagged'])->default('pending');
            $table->enum('verification_method', ['policy_number', 'name_dob', 'id_passport', 'phone', 'email', 'visit_id'])->nullable();
            $table->integer('name_similarity_score')->nullable();
            $table->boolean('name_match')->default(false);
            $table->boolean('dob_match')->default(false);
            $table->boolean('id_match')->default(false);
            $table->boolean('phone_match')->default(false);
            $table->boolean('email_match')->default(false);
            
            // Review information
            $table->text('mismatch_reasons')->nullable();
            $table->text('review_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            
            // Metadata
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies')->onDelete('cascade');
            $table->foreign('policy_id')->references('id')->on('policies')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index('visit_id');
            $table->index('insurance_company_id');
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_identity_verifications');
    }
};
