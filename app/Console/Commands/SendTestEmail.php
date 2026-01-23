<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify mail configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Sending test email to: {$email}");
        $this->info("Mail driver: " . config('mail.default'));
        $this->info("From: " . config('mail.from.address') . ' (' . config('mail.from.name') . ')');
        
        try {
            Mail::raw('This is a test email from the third-party system. If you receive this, email configuration is working correctly!', function (Message $message) use ($email) {
                $message->to($email)
                        ->subject('Test Email - Third-Party System')
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            $this->info('✓ Email sent successfully!');
            
            if (config('mail.default') === 'log') {
                $this->warn('Note: Using "log" mailer - email was written to storage/logs/laravel.log instead of being sent.');
                $this->info('Check: storage/logs/laravel.log to see the email content.');
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Failed to send email: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
