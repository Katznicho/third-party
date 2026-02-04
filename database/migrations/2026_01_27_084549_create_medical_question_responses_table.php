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
        // Check if table already exists (might have been created by a later migration or fix migration)
        if (!Schema::hasTable('medical_question_responses')) {
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
        } else {
            // Table exists, just ensure all columns are present
            Schema::table('medical_question_responses', function (Blueprint $table) {
                if (!Schema::hasColumn('medical_question_responses', 'triggers_exclusion')) {
                    $table->boolean('triggers_exclusion')->default(false)->after('additional_info');
                }
                if (!Schema::hasColumn('medical_question_responses', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_question_responses');
    }
};
