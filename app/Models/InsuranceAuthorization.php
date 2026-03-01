<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceAuthorization extends Model
{
    protected $fillable = [
        'insurance_company_id',
        'policy_id',
        'kashtre_invoice_id',
        'external_invoice_number',
        'total_amount',
        'client_total',
        'insurance_total',
        'breakdown',
        'status',
        'confirmation_code',
        'authorization_reference',
        'requested_at',
        'completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'client_total' => 'decimal:2',
            'insurance_total' => 'decimal:2',
            'breakdown' => 'array',
            'metadata' => 'array',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }
}
