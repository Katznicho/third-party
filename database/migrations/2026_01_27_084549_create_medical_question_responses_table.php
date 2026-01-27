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
        Schema::create('medical_question_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('medical_question_id')->constrained()->onDelete('cascade');
            $table->string('response')->nullable(); // 'yes', 'no', or other response
            $table->text('additional_info')->nullable(); // JSON for complex responses (e.g., medication table)
            $table->boolean('triggers_exclusion')->default(false); // Whether this response triggers exclusion
            $table->timestamps();
            
            // Ensure one response per client per question
            $table->unique(['client_id', 'medical_question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_question_responses');
    }
};
