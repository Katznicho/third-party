<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'code',
        'description',
        'is_mandatory',
        'requires_maternity_wait',
        'requires_optical_dental_pair',
        'waiting_period_days',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'requires_maternity_wait' => 'boolean',
            'requires_optical_dental_pair' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function policyBenefits(): HasMany
    {
        return $this->hasMany(PolicyBenefit::class);
    }

    public function preAuthorizations(): HasMany
    {
        return $this->hasMany(PreAuthorization::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_service_category')
            ->withPivot(['benefit_amount', 'copay_percentage', 'deductible_amount', 'waiting_period_days', 'is_enabled'])
            ->withTimestamps();
    }
}
