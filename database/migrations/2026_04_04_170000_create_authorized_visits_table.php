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
        Schema::create('authorized_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('insurance_company_id')->constrained('insurance_companies')->cascadeOnDelete();
            $table->string('kashtre_client_id')->nullable();
            $table->string('visit_id')->nullable();
            $table->date('visit_date');
            $table->dateTime('expiry_at')->nullable();
            $table->enum('status', ['active', 'expired', 'completed', 'cancelled'])->default('active');
            $table->string('services_category')->nullable();
            $table->text('notes')->nullable();
            $table->json('sync_data')->nullable(); // Store complete sync data from kashtre
            $table->timestamps();
            
            $table->index(['kashtre_client_id']);
            $table->index(['visit_id']);
            $table->index(['status']);
            $table->index(['insurance_company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authorized_visits');
    }
};
