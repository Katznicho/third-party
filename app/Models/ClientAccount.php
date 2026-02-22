<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'insurance_company_id',
        'account_number',
        'account_type',
        'status',
        'opening_balance',
        'current_balance',
        'total_debits',
        'total_credits',
        'available_balance',
        'opened_date',
        'last_transaction_date',
        'last_statement_date',
        'auto_generate_statements',
        'statement_frequency',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'total_debits' => 'decimal:2',
        'total_credits' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'opened_date' => 'date',
        'last_transaction_date' => 'date',
        'last_statement_date' => 'date',
        'auto_generate_statements' => 'boolean',
        'metadata' => 'array',
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    /**
     * Generate a unique 12-digit account number based on insurance company settings
     */
    public static function generateAccountNumber(InsuranceCompany $insuranceCompany): string
    {
        // Get settings with defaults
        $format = $insuranceCompany->account_number_format ?? '{COMPANY}{YEAR}{RANDOM}';
        $randomLength = $insuranceCompany->account_number_random_length ?? 6;
        $randomType = $insuranceCompany->account_number_random_type ?? 'numeric';
        $companyCodeLength = $insuranceCompany->account_number_company_code_length ?? 3;
        
        // Generate company code prefix
        $companyCode = strtoupper(substr($insuranceCompany->code ?? 'ACC', 0, $companyCodeLength));
        $year = now()->format('Y');
        $year2 = now()->format('y');
        
        // Calculate how many digits we need for random part to make total 12 digits
        $usedDigits = 0;
        if (strpos($format, '{COMPANY}') !== false) {
            $usedDigits += strlen($companyCode);
        }
        if (strpos($format, '{YEAR}') !== false) {
            $usedDigits += 4; // YYYY
        }
        if (strpos($format, '{YEAR2}') !== false) {
            $usedDigits += 2; // YY
        }
        if (strpos($format, '{MONTH}') !== false) {
            $usedDigits += 2;
        }
        if (strpos($format, '{DAY}') !== false) {
            $usedDigits += 2;
        }
        
        // Calculate random part length to ensure total is 12 digits
        $requiredRandomLength = 12 - $usedDigits;
        if ($requiredRandomLength < 1) {
            $requiredRandomLength = 6; // Fallback minimum
        }
        
        // Generate random part based on type
        $randomPart = self::generateRandomPart($requiredRandomLength, $randomType);
        
        // Replace placeholders in format
        $accountNumber = $format;
        $accountNumber = str_replace('{COMPANY}', $companyCode, $accountNumber);
        $accountNumber = str_replace('{YEAR}', $year, $accountNumber);
        $accountNumber = str_replace('{YEAR2}', $year2, $accountNumber);
        $accountNumber = str_replace('{MONTH}', now()->format('m'), $accountNumber);
        $accountNumber = str_replace('{DAY}', now()->format('d'), $accountNumber);
        $accountNumber = str_replace('{RANDOM}', $randomPart, $accountNumber);
        
        // Remove any non-numeric characters and ensure it's exactly 12 digits
        $accountNumber = preg_replace('/[^0-9]/', '', $accountNumber);
        
        // Pad or truncate to exactly 12 digits
        if (strlen($accountNumber) < 12) {
            $accountNumber = str_pad($accountNumber, 12, '0', STR_PAD_RIGHT);
        } elseif (strlen($accountNumber) > 12) {
            $accountNumber = substr($accountNumber, 0, 12);
        }
        
        // Ensure uniqueness
        $attempts = 0;
        $originalAccountNumber = $accountNumber;
        while (self::where('account_number', $accountNumber)->exists() && $attempts < 100) {
            $randomPart = self::generateRandomPart($requiredRandomLength, $randomType);
            $accountNumber = $format;
            $accountNumber = str_replace('{COMPANY}', $companyCode, $accountNumber);
            $accountNumber = str_replace('{YEAR}', $year, $accountNumber);
            $accountNumber = str_replace('{YEAR2}', $year2, $accountNumber);
            $accountNumber = str_replace('{MONTH}', now()->format('m'), $accountNumber);
            $accountNumber = str_replace('{DAY}', now()->format('d'), $accountNumber);
            $accountNumber = str_replace('{RANDOM}', $randomPart, $accountNumber);
            $accountNumber = preg_replace('/[^0-9]/', '', $accountNumber);
            
            if (strlen($accountNumber) < 12) {
                $accountNumber = str_pad($accountNumber, 12, '0', STR_PAD_RIGHT);
            } elseif (strlen($accountNumber) > 12) {
                $accountNumber = substr($accountNumber, 0, 12);
            }
            
            $attempts++;
            if ($attempts > 100) {
                // Fallback: use timestamp-based number
                $accountNumber = substr(str_replace(['-', ' ', ':'], '', now()->toDateTimeString()), 0, 12);
                $accountNumber = str_pad($accountNumber, 12, '0', STR_PAD_RIGHT);
            }
        }
        
        return $accountNumber;
    }
    
    /**
     * Generate random part for account number
     */
    private static function generateRandomPart(int $length, string $type): string
    {
        $characters = '';
        if ($type === 'numeric') {
            $characters = '0123456789';
        } elseif ($type === 'alphabetic') {
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        } else { // alphanumeric
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        
        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $random;
    }
}
