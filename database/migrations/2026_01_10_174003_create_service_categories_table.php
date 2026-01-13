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
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Inpatient, Outpatient, Maternity, Optical, Dental, Funeral Expenses, Hospital Cash, Life Cover
            $table->string('slug')->unique();
            $table->string('code')->unique(); // INP, OUT, MAT, OPT, DEN, FUN, HOS, LIF
            $table->text('description')->nullable();
            $table->boolean('is_mandatory')->default(false); // Inpatient is mandatory
            $table->boolean('requires_maternity_wait')->default(false); // Maternity requires prior year payment
            $table->boolean('requires_optical_dental_pair')->default(false); // Optical and Dental must be together
            $table->integer('waiting_period_days')->default(0); // Waiting period in days
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_categories');
    }
};
