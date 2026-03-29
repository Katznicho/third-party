<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only history of deductible applied per visit. Rows are created only in
 * RecordClientPortionService after a completed client-portion payment—not at authorization time.
 */
class PolicyDeductibleLedger extends Model
{
    protected $fillable = [
        'insurance_company_id',
        'policy_id',
        'authorization_id',
        'kashtre_invoice_id',
        'external_invoice_number',
        'change_type',
        'deductible_before',
        'amount_that_reduces_deductible',
        'deductible_after',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'deductible_before' => 'decimal:2',
            'amount_that_reduces_deductible' => 'decimal:2',
            'deductible_after' => 'decimal:2',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function authorization(): BelongsTo
    {
        return $this->belongsTo(InsuranceAuthorization::class, 'authorization_id');
    }
}

