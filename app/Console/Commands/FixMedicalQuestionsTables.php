<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

class FixMedicalQuestionsTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'medical-questions:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix medical questions tables if they are missing or broken';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking medical questions tables...');

        // Check if medical_questions table exists, create if not
        if (!Schema::hasTable('medical_questions')) {
            $this->warn('medical_questions table does not exist. Creating...');
            
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
            
            $this->info('✓ medical_questions table created successfully');
        } else {
            $this->info('✓ medical_questions table exists');
        }

        // Check if medical_question_responses table exists
        if (Schema::hasTable('medical_question_responses')) {
            $this->info('✓ medical_question_responses table exists');
            
            // Check if foreign key exists
            try {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'medical_question_responses' 
                    AND REFERENCED_TABLE_NAME = 'medical_questions'
                ");

                if (empty($foreignKeys)) {
                    $this->warn('Foreign key missing. Recreating medical_question_responses table...');
                    
                    // Drop and recreate
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
                    
                    $this->info('✓ medical_question_responses table recreated with proper foreign keys');
                } else {
                    $this->info('✓ Foreign key exists');
                }
            } catch (\Exception $e) {
                $this->error('Error checking foreign keys: ' . $e->getMessage());
            }
        } else {
            $this->warn('medical_question_responses table does not exist. Creating...');
            
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
            
            $this->info('✓ medical_question_responses table created successfully');
        }

        $this->info('All medical questions tables are now fixed!');
        return 0;
    }
}
