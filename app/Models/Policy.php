<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Policy extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_number',
        'insurance_company_id',
        'principal_member_id',
        'plan_type',
        'inception_date',
        'expiry_date',
        'desired_start_date',
        'total_premium',
        'insurance_training_levy',
        'stamp_duty',
        'total_premium_due',
        'agent_broker_name',
        'status',
        'is_paid',
        'payment_date',
        'has_deductible',
        'copay_amount',
        'coinsurance_percentage',
        'deductible_amount',
        'copay_max_limit',
        'copay_contributes_to_deductible',
        'coinsurance_contributes_to_deductible',
        'telemedicine_only',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'inception_date' => 'date',
            'expiry_date' => 'date',
            'desired_start_date' => 'date',
            'total_premium' => 'decimal:2',
            'insurance_training_levy' => 'decimal:2',
            'stamp_duty' => 'decimal:2',
            'total_premium_due' => 'decimal:2',
            'is_paid' => 'boolean',
            'payment_date' => 'date',
            'has_deductible' => 'boolean',
            'copay_amount' => 'decimal:2',
            'coinsurance_percentage' => 'decimal:2',
            'deductible_amount' => 'decimal:2',
            'copay_max_limit' => 'decimal:2',
            'copay_contributes_to_deductible' => 'boolean',
            'coinsurance_contributes_to_deductible' => 'boolean',
            'telemedicine_only' => 'boolean',
        ];
    }

    // Relationships
    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function principalMember(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'principal_member_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(Client::class, 'principal_member_id')->where('type', 'dependent');
    }

    public function benefits(): HasMany
    {
        return $this->hasMany(PolicyBenefit::class);
    }

    public function preAuthorizations(): HasMany
    {
        return $this->hasMany(PreAuthorization::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentResponsibilities(): HasMany
    {
        return $this->hasMany(PaymentResponsibility::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * A policy is authorizable if it's active, OR if it's pending payment
     * but still within the grace period.
     */
    public function isAuthorizable(): bool
    {
        if ($this->status === 'active') {
            return true;
        }

        if ($this->status === 'pending_payment') {
            return $this->isWithinGracePeriod();
        }

        return false;
    }

    public function isWithinGracePeriod(): bool
    {
        $pendingPayment = Payment::where('policy_id', $this->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($pendingPayment) {
            $dueAt = $pendingPayment->payment_metadata['due_at'] ?? null;
            if ($dueAt) {
                return now()->lte(Carbon::parse($dueAt)->endOfDay());
            }
        }

        $client = $this->principalMember;
        $graceDays = $client?->premium_grace_days;

        if ($graceDays === null && $this->insuranceCompany) {
            $paymentMethod = $pendingPayment->payment_method ?? 'cash';
            $graceDays = $this->insuranceCompany->getGracePeriodForMethod($paymentMethod);
        }

        if ($graceDays !== null && $graceDays > 0) {
            return now()->lte($this->created_at->addDays($graceDays)->endOfDay());
        }

        return false;
    }

    public function getGracePeriodEnd(): ?Carbon
    {
        $pendingPayment = Payment::where('policy_id', $this->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($pendingPayment) {
            $dueAt = $pendingPayment->payment_metadata['due_at'] ?? null;
            if ($dueAt) {
                return Carbon::parse($dueAt)->endOfDay();
            }
        }

        return null;
    }

    public function isExpired(): bool
    {
        return $this->expiry_date < now();
    }

    /**
     * Check if copay contributes to deductible
     * Uses policy-level setting or falls back to insurance company default
     */
    public function copayContributesToDeductible(): bool
    {
        if ($this->copay_contributes_to_deductible !== null) {
            return $this->copay_contributes_to_deductible;
        }
        
        return $this->insuranceCompany->copay_contributes_to_deductible ?? false;
    }

    /**
     * Check if coinsurance contributes to deductible
     * Uses policy-level setting or falls back to insurance company default
     */
    public function coinsuranceContributesToDeductible(): bool
    {
        if ($this->coinsurance_contributes_to_deductible !== null) {
            return $this->coinsurance_contributes_to_deductible;
        }
        
        return $this->insuranceCompany->coinsurance_contributes_to_deductible ?? false;
    }

    /**
     * Calculate deductible contribution from copay and coinsurance amounts
     * 
     * @param float $copayAmount The copay amount paid
     * @param float $coinsuranceAmount The coinsurance amount paid
     * @return float Total amount that contributes to deductible
     */
    public function calculateDeductibleContribution(float $copayAmount = 0, float $coinsuranceAmount = 0): float
    {
        $contribution = 0;
        
        if ($this->copayContributesToDeductible() && $copayAmount > 0) {
            $contribution += $copayAmount;
        }
        
        if ($this->coinsuranceContributesToDeductible() && $coinsuranceAmount > 0) {
            $contribution += $coinsuranceAmount;
        }
        
        return $contribution;
    }
}
