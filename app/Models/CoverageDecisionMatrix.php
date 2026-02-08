<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoverageDecisionMatrix extends Model
{
    use HasFactory;

    protected $table = 'coverage_decision_matrix';

    protected $fillable = [
        'insurance_company_id',
        'rule_name',
        'description',
        'condition_type',
        'condition_config',
        'action',
        'rejection_message',
        'review_notes_template',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'condition_config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForInsuranceCompany($query, $insuranceCompanyId)
    {
        return $query->where('insurance_company_id', $insuranceCompanyId);
    }

    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }
}
