<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'code',
        'description',
        'insurance_company_id',
        'is_active',
        'sort_order',
        // Age Limits
        'min_enrollment_age',
        'max_enrollment_age',
        // Dependent Coverage
        'dependent_coverage_multiplier',
        'dependent_multiplier_tiers',
        'dependent_multiplier_floor',
        // Coverage Limits
        'annual_max_coverage',
        'lifetime_max_coverage',
        'per_incident_max_coverage',
        // Plan Image
        'image_path',
        // Terms & Conditions
        'terms_and_conditions',
        'terms_link',
        // Premium Calculation
        'base_premium',
        'premium_calculation_method',
        'insurance_training_levy_percentage',
        'stamp_duty_amount',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'min_enrollment_age' => 'integer',
            'max_enrollment_age' => 'integer',
            'dependent_coverage_multiplier' => 'decimal:2',
            'dependent_multiplier_tiers' => 'array',
            'dependent_multiplier_floor' => 'decimal:2',
            'annual_max_coverage' => 'decimal:2',
            'lifetime_max_coverage' => 'decimal:2',
            'per_incident_max_coverage' => 'decimal:2',
            'base_premium' => 'decimal:2',
            'insurance_training_levy_percentage' => 'decimal:2',
            'stamp_duty_amount' => 'decimal:2',
        ];
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function serviceCategories(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCategory::class, 'plan_service_category')
            ->withPivot(['benefit_amount', 'base_amount', 'coverage_percent', 'waiting_period_days', 'is_enabled'])
            ->withTimestamps();
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Calculate dependent premium using tiered multipliers
     * 
     * @param float $basePremium
     * @param int $numberOfDependents
     * @return float
     */
    public function calculateDependentPremium(float $basePremium, int $numberOfDependents): float
    {
        if ($numberOfDependents <= 0) {
            return 0;
        }

        $tiers = $this->dependent_multiplier_tiers ?? [];
        $floor = $this->dependent_multiplier_floor ?? null;
        $legacyMultiplier = $this->dependent_coverage_multiplier ?? 0.50;

        // If no tiers are configured, use legacy multiplier
        if (empty($tiers)) {
            return $basePremium * $legacyMultiplier * $numberOfDependents;
        }

        $totalPremium = 0;
        
        for ($i = 0; $i < $numberOfDependents; $i++) {
            $tierIndex = $i; // 0-based index
            
            if (isset($tiers[$tierIndex])) {
                // Use tier multiplier
                $multiplier = $tiers[$tierIndex];
            } elseif ($floor !== null) {
                // Use floor limit for dependents beyond defined tiers
                $multiplier = $floor;
            } else {
                // Fallback to legacy multiplier if no floor is set
                $multiplier = $legacyMultiplier;
            }
            
            $totalPremium += $basePremium * $multiplier;
        }

        return $totalPremium;
    }

}
