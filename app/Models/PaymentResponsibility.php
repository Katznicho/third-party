<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentResponsibility extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_id', 'policy_benefit_id', 'pre_authorization_id', 'transaction_id',
        'responsibility_type', 'total_amount', 'insurance_pays', 'client_pays', 'other_payer_pays',
        'client_percentage', 'insurance_percentage', 'is_deductible_applicable', 'deductible_amount',
        'deductible_used', 'deductible_remaining', 'deductible_type', 'is_copay_applicable',
        'copay_amount', 'copay_percentage', 'shared_payment_percentage', 'shared_payment_description',
        'status', 'calculation_date', 'due_date', 'paid_date', 'payment_notes', 'description',
        'calculation_notes', 'calculation_details',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'insurance_pays' => 'decimal:2',
        'client_pays' => 'decimal:2',
        'other_payer_pays' => 'decimal:2',
        'client_percentage' => 'decimal:2',
        'insurance_percentage' => 'decimal:2',
        'is_deductible_applicable' => 'boolean',
        'deductible_amount' => 'decimal:2',
        'deductible_used' => 'decimal:2',
        'deductible_remaining' => 'decimal:2',
        'is_copay_applicable' => 'boolean',
        'copay_amount' => 'decimal:2',
        'copay_percentage' => 'decimal:2',
        'shared_payment_percentage' => 'decimal:2',
        'calculation_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'calculation_details' => 'array',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function policyBenefit(): BelongsTo
    {
        return $this->belongsTo(PolicyBenefit::class);
    }

    public function preAuthorization(): BelongsTo
    {
        return $this->belongsTo(PreAuthorization::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
