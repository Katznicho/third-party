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
        Schema::create('business_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_company_id'); // The main insurance company
            $table->unsignedBigInteger('connected_business_id'); // The connected company (from Kashtre)
            $table->string('connection_type')->default('client'); // client, partner, etc.
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies')->onDelete('cascade');
            $table->foreign('connected_business_id')->references('id')->on('insurance_companies')->onDelete('cascade');
            
            // Ensure unique connections
            $table->unique(['insurance_company_id', 'connected_business_id'], 'unique_business_connection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_connections');
    }
};
