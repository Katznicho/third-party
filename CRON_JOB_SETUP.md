# Cron Job Setup for Payment Status Checking (Third-Party System)

This document explains how to set up the cron job for automatic YoAPI payment status checking in the third-party system.

## Overview

The system uses a Laravel scheduled command (`payments:check-status`) that runs every minute to check the status of pending YoAPI payments and update them accordingly.

## Current Configuration

### Laravel Scheduler
- **File**: `app/Providers/AppServiceProvider.php`
- **Command**: `payments:check-status`
- **Schedule**: Every minute (`->everyMinute()`)
- **Purpose**: Check and update YoAPI payment statuses using `transaction_id` field from Payment records

### Command Details
- **File**: `app/Console/Commands/CheckPaymentStatus.php`
- **Signature**: `payments:check-status`
- **Description**: Check and update YoAPI payment statuses for pending payments
- **Logging**: Comprehensive logging to `storage/logs/laravel.log`

## Server Setup

### Option 1: Using the Setup Script (Recommended)

1. **Upload the setup script to your server**:
   ```bash
   # Make sure you're in the Laravel project root directory
   chmod +x setup-cron-job.sh
   ./setup-cron-job.sh
   ```

### Option 2: Manual Setup

1. **Access your server via SSH**

2. **Navigate to your Laravel project directory**:
   ```bash
   cd /path/to/your/laravel/project
   ```

3. **Add the cron job to crontab**:
   ```bash
   crontab -e
   ```

4. **Add this line to the crontab**:
   ```bash
   * * * * * cd /path/to/your/laravel/project && php artisan schedule:run >> /dev/null 2>&1
   ```

5. **Save and exit the editor**

## Verification

### Check if the cron job is running:
```bash
# View current crontab
crontab -l

# Test the command manually
php artisan payments:check-status

# Check Laravel logs
tail -f storage/logs/laravel.log
```

### Expected Log Output:
```
[2026-02-16 10:30:00] local.INFO: === CRON JOB STARTED: CheckPaymentStatus (Third-Party) ===
[2026-02-16 10:30:00] local.INFO: Found pending mobile money payments
[2026-02-16 10:30:01] local.INFO: YoAPI status check response for payment 123
[2026-02-16 10:30:01] local.INFO: Payment status updated to completed
[2026-02-16 10:30:01] local.INFO: === CRON JOB COMPLETED: CheckPaymentStatus (Third-Party) ===
```

## How It Works

1. **Every minute**, the Laravel scheduler runs `php artisan schedule:run`
2. **The scheduler** executes the `payments:check-status` command
3. **The command** queries for pending payments with:
   - `status = 'pending'`
   - `payment_method = 'mobile_money'`
   - `transaction_id` is not null
   - `payment_metadata` is not null
4. **For each payment**, it calls YoAPI's `ac_transaction_check_status` using the `transaction_id`
5. **Based on the response**, it updates the payment status:
   - `SUCCEEDED` → `status = 'completed'`, sets `cleared_date` and `processed_at`
   - `FAILED` → `status = 'failed'`, sets `failure_reason`
   - `PENDING` → Updates metadata with latest status check, keeps status as `pending`
6. **All actions are logged** for debugging and monitoring

## Troubleshooting

### Common Issues:

1. **Cron job not running**:
   - Check if crontab is properly configured: `crontab -l`
   - Verify the path in the cron command is correct
   - Check server cron service is running: `systemctl status cron`

2. **Command not found**:
   - Ensure PHP is in the system PATH
   - Use full path to PHP: `/usr/bin/php artisan schedule:run`

3. **Permission issues**:
   - Ensure the web server user has access to the Laravel project
   - Check file permissions: `chmod -R 755 storage bootstrap/cache`

4. **No payments being processed**:
   - Check if there are pending mobile money payments in the database
   - Verify the `transaction_id` field is populated
   - Check Laravel logs for error messages

### Manual Testing:
```bash
# Test the command directly
php artisan payments:check-status

# Check for pending payments
php artisan tinker
>>> App\Models\Payment::where('status', 'pending')->where('payment_method', 'mobile_money')->count()
```

## Monitoring

### Log Files:
- **Laravel Logs**: `storage/logs/laravel.log`
- **System Cron Logs**: `/var/log/cron` (varies by system)

### Key Metrics to Monitor:
- Number of pending payments processed per minute
- Number of payments completed vs failed
- Average time to complete payment verification
- YoAPI API response times

## Payment Status Flow

1. **Payment Created**: When invoice is marked as paid, payment record is created with `status = 'pending'` and `transaction_id` from Yo Payments
2. **Cron Job Checks**: Every minute, cron job checks all pending mobile money payments
3. **YoAPI Status Check**: For each payment, calls YoAPI to check transaction status
4. **Status Update**:
   - If `SUCCEEDED`: Updates payment to `completed`, sets `cleared_date` and `processed_at`
   - If `FAILED`: Updates payment to `failed`, sets `failure_reason`
   - If `PENDING`: Updates metadata with latest check time, keeps status as `pending`
5. **Payment Complete**: Payment appears as completed in the `/payments` page
