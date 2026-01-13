<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolicyBenefit extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_id', 'service_category_id', 'benefit_amount', 'used_amount', 'remaining_amount',
        'hospital_cash_per_day', 'hospital_cash_max_days', 'life_cover_amount', 'allowed_services',
        'copay_percentage', 'deductible_amount', 'deductible_type', 'shared_payment_percentage',
        'payment_notes', 'is_enabled', 'effective_date', 'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'benefit_amount' => 'decimal:2',
            'used_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'hospital_cash_per_day' => 'decimal:2',
            'life_cover_amount' => 'decimal:2',
            'allowed_services' => 'array',
            'copay_percentage' => 'decimal:2',
            'deductible_amount' => 'decimal:2',
            'shared_payment_percentage' => 'decimal:2',
            'is_enabled' => 'boolean',
            'effective_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function paymentResponsibilities(): HasMany
    {
        return $this->hasMany(PaymentResponsibility::class);
    }

    public function updateRemainingAmount(): void
    {
        $this->remaining_amount = $this->benefit_amount - $this->used_amount;
        $this->save();
    }
}
