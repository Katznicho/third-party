<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if medical_questions table exists, create if not
        if (!Schema::hasTable('medical_questions')) {
            Schema::create('medical_questions', function (Blueprint $table) {
                $table->id();
                $table->text('question_text');
                $table->enum('question_type', ['yes_no', 'text', 'date', 'number'])->default('yes_no');
                $table->boolean('has_exclusion_list')->default(false);
                $table->json('exclusion_keywords')->nullable();
                $table->boolean('requires_additional_info')->default(false);
                $table->enum('additional_info_type', ['text', 'date', 'table'])->nullable();
                $table->text('additional_info_label')->nullable();
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Check if medical_question_responses table exists
        if (Schema::hasTable('medical_question_responses')) {
            // Check if foreign key exists
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'medical_question_responses' 
                AND REFERENCED_TABLE_NAME = 'medical_questions'
            ");

            // If foreign key doesn't exist, we need to fix the table structure
            if (empty($foreignKeys)) {
                // Since medical_questions didn't exist, any data in responses would be orphaned
                // Drop the table and recreate with proper structure
                Schema::dropIfExists('medical_question_responses');
                
                Schema::create('medical_question_responses', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('client_id')->constrained()->onDelete('cascade');
                    $table->foreignId('medical_question_id')->constrained()->onDelete('cascade');
                    $table->string('response')->nullable();
                    $table->text('additional_info')->nullable();
                    $table->boolean('triggers_exclusion')->default(false);
                    $table->timestamps();
                    
                    $table->unique(['client_id', 'medical_question_id']);
                });
            } else {
                // Table exists with foreign key, just ensure all columns are present
                Schema::table('medical_question_responses', function (Blueprint $table) {
                    if (!Schema::hasColumn('medical_question_responses', 'triggers_exclusion')) {
                        $table->boolean('triggers_exclusion')->default(false)->after('additional_info');
                    }
                    if (!Schema::hasColumn('medical_question_responses', 'created_at')) {
                        $table->timestamps();
                    }
                });
            }
        } else {
            // Table doesn't exist, create it
            Schema::create('medical_question_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->foreignId('medical_question_id')->constrained()->onDelete('cascade');
                $table->string('response')->nullable();
                $table->text('additional_info')->nullable();
                $table->boolean('triggers_exclusion')->default(false);
                $table->timestamps();
                
                $table->unique(['client_id', 'medical_question_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop tables in down() to avoid data loss
        // If needed, manually drop tables
    }
};
