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
        Schema::create('medical_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question_text');
            $table->enum('question_type', ['yes_no', 'text', 'date', 'number'])->default('yes_no');
            $table->boolean('has_exclusion_list')->default(false);
            $table->json('exclusion_keywords')->nullable(); // Keywords that trigger exclusion when "yes" is selected
            $table->boolean('requires_additional_info')->default(false);
            $table->enum('additional_info_type', ['text', 'date', 'table'])->nullable(); // Type of additional info needed
            $table->text('additional_info_label')->nullable(); // Label for additional info field
            $table->integer('order')->default(0); // Display order
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_questions');
    }
};
