<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'principal_member_id',
        'plan_id',
        'surname',
        'first_name',
        'other_names',
        'title',
        'id_passport_no',
        'gender',
        'tin',
        'date_of_birth',
        'marital_status',
        'height',
        'weight',
        'employer_name',
        'occupation',
        'nationality',
        'home_physical_address',
        'office_physical_address',
        'home_telephone',
        'office_telephone',
        'cell_phone',
        'whatsapp_line',
        'email',
        'relation_to_principal',
        'next_of_kin_surname',
        'next_of_kin_first_name',
        'next_of_kin_other_names',
        'next_of_kin_title',
        'next_of_kin_relation',
        'next_of_kin_id_passport_no',
        'next_of_kin_cell_phone',
        'next_of_kin_email',
        'next_of_kin_post_address',
        'next_of_kin_physical_address',
        'medical_history',
        'regular_medications',
        'has_deductible',
        'deductible_amount',
        'insurance_payable_percentage',
        'premium_grace_days',
        'active_period_days',
        'telemedicine_only',
        'is_active',
        'insurance_company_id',
        'registered_via_open_enrollment',
        'kashtre_client_id',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'medical_history' => 'array',
            'regular_medications' => 'array',
            'has_deductible' => 'boolean',
            'deductible_amount' => 'decimal:2',
            'insurance_payable_percentage' => 'decimal:2',
            'premium_grace_days' => 'integer',
            'active_period_days' => 'integer',
            'telemedicine_only' => 'boolean',
            'is_active' => 'boolean',
            'registered_via_open_enrollment' => 'boolean',
        ];
    }

    // Relationships
    public function principalMember(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'principal_member_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(Client::class, 'principal_member_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class, 'principal_member_id');
    }

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class, 'principal_member_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function medicalQuestionResponses(): HasMany
    {
        return $this->hasMany(MedicalQuestionResponse::class);
    }

    public function verificationOtps(): HasMany
    {
        return $this->hasMany(VerificationOtp::class);
    }

    public function account(): HasOne
    {
        return $this->hasOne(ClientAccount::class);
    }

    public function authorizedVisits(): HasMany
    {
        return $this->hasMany(AuthorizedVisit::class);
    }

    /**
     * Check if client has any exclusion-triggering responses
     */
    public function hasExclusions(): bool
    {
        return $this->medicalQuestionResponses()
            ->where('triggers_exclusion', true)
            ->exists();
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->surname} {$this->other_names}");
    }

    public function isPrincipal(): bool
    {
        return $this->type === 'principal';
    }

    public function isDependent(): bool
    {
        return $this->type === 'dependent';
    }

    /**
     * Client portion payments and credits are stored on the principal member's account.
     * Use this when resolving ClientAccount / balances for dependents.
     */
    public function accountBalanceClient(): self
    {
        if ($this->isDependent() && $this->principal_member_id) {
            $this->loadMissing('principalMember');

            return $this->principalMember ?? $this;
        }

        return $this;
    }

    /**
     * Transactions and payments may be recorded on the principal while viewing a dependent (same policy family).
     */
    public function accountActivityClientIds(): array
    {
        $ids = collect([(int) $this->id]);
        if ($this->principal_member_id) {
            $ids->push((int) $this->principal_member_id);
        }
        if ($this->isPrincipal()) {
            $ids = $ids->merge($this->dependents()->pluck('id'));
        }

        return $ids->unique()->values()->all();
    }
}
