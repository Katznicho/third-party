<?php

namespace App\Console\Commands;

use App\Models\InsuranceCompany;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateInsuranceCompanyCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insurance-companies:update-codes 
                            {--dry-run : Show what would be updated without making changes}
                            {--force : Force update even if code is already 8 digits}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all insurance companies to have unique 8-digit numeric codes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Starting insurance company code update...');
        $this->newLine();

        // Get all insurance companies
        $companies = InsuranceCompany::all();
        
        if ($companies->isEmpty()) {
            $this->warn('No insurance companies found in the database.');
            return Command::SUCCESS;
        }

        $this->info("Found {$companies->count()} insurance company(ies) to check.");
        $this->newLine();

        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $updates = [];

        foreach ($companies as $company) {
            $currentCode = $company->code;
            
            // Check if code is already 8 digits
            $isValid8Digit = preg_match('/^[0-9]{8}$/', $currentCode);
            
            if ($isValid8Digit && !$force) {
                $this->line("✓ [SKIP] {$company->name} - Already has 8-digit code: {$currentCode}");
                $skipped++;
                continue;
            }

            // Generate new 8-digit code
            $newCode = $this->generateUniqueCode();
            
            if (!$newCode) {
                $this->error("✗ [ERROR] {$company->name} - Failed to generate unique code");
                $errors++;
                continue;
            }

            $updates[] = [
                'id' => $company->id,
                'name' => $company->name,
                'old_code' => $currentCode,
                'new_code' => $newCode,
            ];

            if ($dryRun) {
                $this->line("→ [DRY RUN] {$company->name}");
                $this->line("  Old Code: {$currentCode}");
                $this->line("  New Code: {$newCode}");
            } else {
                try {
                    DB::beginTransaction();
                    
                    $company->code = $newCode;
                    $company->save();
                    
                    DB::commit();
                    
                    $this->info("✓ [UPDATED] {$company->name}");
                    $this->line("  Old Code: {$currentCode}");
                    $this->line("  New Code: {$newCode}");
                    
                    Log::info('Insurance company code updated', [
                        'company_id' => $company->id,
                        'company_name' => $company->name,
                        'old_code' => $currentCode,
                        'new_code' => $newCode,
                    ]);
                    
                    $updated++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("✗ [ERROR] {$company->name} - {$e->getMessage()}");
                    Log::error('Failed to update insurance company code', [
                        'company_id' => $company->id,
                        'company_name' => $company->name,
                        'error' => $e->getMessage(),
                    ]);
                    $errors++;
                }
            }
            
            $this->newLine();
        }

        // Summary
        $this->newLine();
        $this->info('=== Summary ===');
        $this->table(
            ['Status', 'Count'],
            [
                ['Updated', $dryRun ? count($updates) : $updated],
                ['Skipped', $skipped],
                ['Errors', $errors],
                ['Total', $companies->count()],
            ]
        );

        if ($dryRun && !empty($updates)) {
            $this->newLine();
            $this->info('Companies that would be updated:');
            $this->table(
                ['ID', 'Name', 'Old Code', 'New Code'],
                array_map(function ($update) {
                    return [
                        $update['id'],
                        $update['name'],
                        $update['old_code'],
                        $update['new_code'],
                    ];
                }, $updates)
            );
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. No changes were made.');
            $this->info('Run without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }

    /**
     * Generate a unique 8-digit code
     *
     * @return string|null
     */
    private function generateUniqueCode(): ?string
    {
        $maxAttempts = 100;
        $attempts = 0;

        do {
            $code = str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            $attempts++;

            // Check if code already exists
            $exists = InsuranceCompany::where('code', $code)->exists();

            if (!$exists) {
                return $code;
            }

            if ($attempts >= $maxAttempts) {
                Log::error('Failed to generate unique 8-digit code after maximum attempts', [
                    'attempts' => $attempts,
                ]);
                return null;
            }
        } while (true);
    }
}
