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
        Schema::create('verification_otps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->enum('verification_type', ['phone', 'email']); // Type of verification
            $table->string('identifier'); // Phone number or email address
            $table->string('otp', 6); // 6-digit OTP code
            $table->integer('attempts')->default(0); // Number of verification attempts
            $table->timestamp('expires_at'); // OTP expiration time
            $table->timestamp('verified_at')->nullable(); // When OTP was successfully verified
            $table->string('ip_address', 45)->nullable(); // IP address of requester
            $table->timestamps();

            // Foreign key to clients table
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            
            // Indexes for faster lookups
            $table->index(['client_id', 'verification_type', 'identifier']);
            $table->index('expires_at');
            $table->index('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_otps');
    }
};
