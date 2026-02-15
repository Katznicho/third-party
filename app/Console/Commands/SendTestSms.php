<?php

namespace App\Console\Commands;

use App\Services\MarzSmsService;
use Illuminate\Console\Command;

class SendTestSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:test {phone : The phone number to send test SMS to (e.g., +256700000000)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test SMS to verify MarzSMS configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        
        $this->info("Sending test SMS to: {$phone}");
        $this->info("Base URL: " . config('services.marzsms.base_url'));
        $this->info("API Key: " . (config('services.marzsms.api_key') ? substr(config('services.marzsms.api_key'), 0, 10) . '...' : 'NOT SET'));
        
        // Check if credentials are configured
        if (!config('services.marzsms.api_key') || !config('services.marzsms.api_secret')) {
            $this->error('✗ MarzSMS credentials are not configured!');
            $this->warn('Please set MARZSMS_API_KEY and MARZSMS_API_SECRET in your .env file');
            return 1;
        }
        
        try {
            $smsService = app(MarzSmsService::class);
            
            // First check balance
            $this->info("\nChecking account balance...");
            $balanceResult = $smsService->getBalance();
            
            if ($balanceResult['success'] ?? false) {
                $balance = $balanceResult['data']['balance'] ?? 0;
                $currency = $balanceResult['data']['currency'] ?? 'UGX';
                $costPerSms = $balanceResult['data']['cost_per_sms'] ?? 0;
                $this->info("✓ Balance: {$balance} {$currency}");
                $this->info("✓ Cost per SMS: {$costPerSms} {$currency}");
                
                if ($balance < $costPerSms) {
                    $this->warn("⚠ Warning: Balance is too low to send SMS!");
                }
            } else {
                $this->warn("⚠ Could not retrieve balance: " . ($balanceResult['message'] ?? 'Unknown error'));
            }
            
            // Send test SMS
            $this->info("\nSending test SMS...");
            $message = 'This is a test SMS from the third-party system. If you receive this, SMS configuration is working correctly!';
            
            $result = $smsService->sendSms($phone, $message);
            
            if ($result['success'] ?? false) {
                $this->info('✓ SMS sent successfully!');
                
                if (isset($result['data'])) {
                    $data = $result['data'];
                    $this->info("✓ Total recipients: " . ($data['total_recipients'] ?? 0));
                    $this->info("✓ Successful: " . ($data['successful'] ?? 0));
                    $this->info("✓ Failed: " . ($data['failed'] ?? 0));
                    $this->info("✓ Total cost: " . ($data['total_cost'] ?? 0) . ' ' . ($data['currency'] ?? 'UGX'));
                    $this->info("✓ Remaining balance: " . ($data['remaining_balance'] ?? 0) . ' ' . ($data['currency'] ?? 'UGX'));
                    
                    if (isset($data['results']) && is_array($data['results'])) {
                        $this->info("\nTransaction details:");
                        foreach ($data['results'] as $index => $transaction) {
                            $this->line("  " . ($index + 1) . ". Recipient: " . ($transaction['recipient'] ?? 'N/A'));
                            $this->line("     Status: " . ($transaction['status'] ?? 'N/A'));
                            $this->line("     Transaction ID: " . ($transaction['transaction_id'] ?? 'N/A'));
                            $this->line("     Cost: " . ($transaction['cost'] ?? 0) . ' ' . ($data['currency'] ?? 'UGX'));
                        }
                    }
                }
                
                return 0;
            } else {
                $this->error('✗ Failed to send SMS: ' . ($result['message'] ?? 'Unknown error'));
                if (isset($result['error'])) {
                    $this->error('Error code: ' . $result['error']);
                }
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('✗ Exception occurred: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
