<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration fixes the issue where a duplicate migration
     * 2026_01_27_084550_create_medical_question_responses_table tries to create
     * a table that was already created by 2026_01_27_084549_create_medical_question_responses_table
     * 
     * This runs BEFORE the duplicate migration (084551 > 084550) to mark it as already run
     */
    public function up(): void
    {
        // Fix: Mark the duplicate migration as run if table already exists
        $duplicateMigration = '2026_01_27_084550_create_medical_question_responses_table';
        
        // Check if this migration is NOT in migrations table (meaning it's pending)
        $migrationExists = DB::table('migrations')
            ->where('migration', $duplicateMigration)
            ->exists();
        
        // If migration is NOT marked as run but table exists, mark it as run
        // This handles the case where the migration file exists on server but table was already created
        if (!$migrationExists && Schema::hasTable('medical_question_responses')) {
            // Get the latest batch number
            $latestBatch = DB::table('migrations')->max('batch') ?? 0;
            
            // Mark the duplicate migration as run to prevent it from trying to create the table again
            try {
                DB::table('migrations')->insert([
                    'migration' => $duplicateMigration,
                    'batch' => $latestBatch + 1,
                ]);
                
                \Log::info('Fixed duplicate migration: marked 2026_01_27_084550_create_medical_question_responses_table as run');
            } catch (\Exception $e) {
                \Log::warning('Could not mark duplicate migration as run', ['error' => $e->getMessage()]);
                // Don't fail the migration if we can't mark it - the table already exists anyway
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the duplicate migration entry if it was added
        DB::table('migrations')
            ->where('migration', '2026_01_27_084550_create_medical_question_responses_table')
            ->delete();
    }
};
