<?php

namespace App\Models;

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

    public function isExpired(): bool
    {
        return $this->expiry_date < now();
    }
}
