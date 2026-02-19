<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InsuranceCompany;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetInsuranceInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:reset-unpaid {code : Insurance company code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all invoices for an insurance company to unpaid status by deleting payment records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $code = strtoupper($this->argument('code'));
        
        $this->info("Resetting invoices for insurance company with code: {$code}");
        
        // Find insurance company
        $insuranceCompany = InsuranceCompany::where('code', $code)->first();
        
        if (!$insuranceCompany) {
            $this->error("Insurance company with code '{$code}' not found.");
            return 1;
        }
        
        $this->info("Found insurance company: {$insuranceCompany->name} (ID: {$insuranceCompany->id})");
        
        // Find all payment records for this insurance company
        // Payments are stored with insurance_company_id in payment_metadata JSON field
        $payments = Payment::whereRaw('JSON_EXTRACT(payment_metadata, "$.insurance_company_id") = ?', [$insuranceCompany->id])
            ->get();
        
        if ($payments->isEmpty()) {
            $this->warn("No payment records found for this insurance company.");
            $this->info("Note: Invoices are stored in Kashtre. To reset invoices there, run:");
            $this->info("  cd /path/to/kashtre && php artisan invoices:reset-unpaid {$code}");
            return 0;
        }
        
        $this->info("Found {$payments->count()} payment record(s) to delete");
        
        // Show summary
        $this->table(
            ['Payment Reference', 'Amount', 'Method', 'Status', 'Kashtre Invoice ID'],
            $payments->map(function($payment) {
                $metadata = $payment->payment_metadata ?? [];
                return [
                    $payment->payment_reference,
                    'UGX ' . number_format($payment->amount, 2),
                    $payment->payment_method ?? 'N/A',
                    $payment->status ?? 'N/A',
                    $metadata['kashtre_invoice_id'] ?? 'N/A',
                ];
            })->toArray()
        );
        
        if (!$this->confirm('Are you sure you want to delete these payment records?', true)) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        DB::beginTransaction();
        
        try {
            $deletedCount = 0;
            foreach ($payments as $payment) {
                $payment->delete();
                $deletedCount++;
            }
            
            DB::commit();
            
            $this->info("\n✅ Successfully deleted {$deletedCount} payment record(s)!");
            $this->info("\nNote: Invoices are stored in Kashtre. To reset invoices there as well, run:");
            $this->info("  cd /path/to/kashtre && php artisan invoices:reset-unpaid {$code}");
            
            Log::info('Payment records deleted for insurance company', [
                'insurance_company_id' => $insuranceCompany->id,
                'insurance_company_code' => $code,
                'payments_deleted' => $deletedCount,
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->error("Error deleting payment records: " . $e->getMessage());
            Log::error('Failed to delete payment records', [
                'insurance_company_id' => $insuranceCompany->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 1;
        }
    }
}
