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
        Schema::create('pre_authorizations', function (Blueprint $table) {
            $table->id();
            $table->string('authorization_number')->unique();
            $table->foreignId('policy_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_category_id')->constrained()->onDelete('cascade');
            
            // Request Details
            $table->text('request_description');
            $table->text('medical_justification')->nullable();
            $table->string('requested_by')->nullable(); // Doctor/Provider name
            $table->string('provider_name')->nullable();
            $table->text('provider_address')->nullable();
            $table->string('provider_phone')->nullable();
            
            // Financial Information
            $table->decimal('requested_amount', 15, 2);
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->decimal('estimated_amount', 15, 2)->nullable();
            
            // Status
            $table->enum('status', [
                'pending',
                'approved',
                'partially_approved',
                'rejected',
                'cancelled',
                'expired'
            ])->default('pending');
            
            // Dates
            $table->date('request_date');
            $table->date('required_date')->nullable(); // Date when service is needed
            $table->date('approval_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('service_date')->nullable(); // Actual service date
            
            // Approval Information
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Visit Information (for authorization of particular visits)
            $table->string('visit_type')->nullable(); // inpatient, outpatient, emergency
            $table->integer('visit_number')->nullable(); // Visit sequence number
            $table->date('visit_start_date')->nullable();
            $table->date('visit_end_date')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_authorizations');
    }
};
