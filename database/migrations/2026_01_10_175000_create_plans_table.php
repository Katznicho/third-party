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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Prestige, Executive, Standard Plus, Standard, Regular, Budget
            $table->string('slug')->unique();
            $table->string('code')->unique(); // PRE, EXE, STD+, STD, REG, BUD
            $table->text('description')->nullable();
            $table->unsignedBigInteger('insurance_company_id'); // Plans can be specific to insurance companies
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
