<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuthorizationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_company_id',
        'rule_name',
        'description',
        'rule_type',
        'conditions',
        'action',
        'partial_approval_percentage',
        'partial_approval_amount',
        'priority',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'conditions' => 'array',
        'metadata' => 'array',
        'partial_approval_percentage' => 'decimal:2',
        'partial_approval_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Relationships
     */
    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuthorizationAuditLog::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForInsuranceCompany($query, $insuranceCompanyId)
    {
        return $query->where('insurance_company_id', $insuranceCompanyId);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    public function scopeByRuleType($query, $ruleType)
    {
        return $query->where('rule_type', $ruleType);
    }

    /**
     * Helper methods
     */
    public function isAmountBased(): bool
    {
        return $this->rule_type === 'amount';
    }

    public function isServiceCategoryBased(): bool
    {
        return $this->rule_type === 'service_category';
    }

    public function isCombined(): bool
    {
        return $this->rule_type === 'combined';
    }

    /**
     * Get conditions in a readable format
     */
    public function getConditionsDescription(): string
    {
        $conditions = $this->conditions ?? [];
        $parts = [];

        if (isset($conditions['min_amount'])) {
            $parts[] = "Amount ≥ " . number_format($conditions['min_amount'], 2);
        }
        if (isset($conditions['max_amount'])) {
            $parts[] = "Amount ≤ " . number_format($conditions['max_amount'], 2);
        }
        if (isset($conditions['service_category_ids'])) {
            $parts[] = "Service Categories: " . count($conditions['service_category_ids']) . " selected";
        }
        if (isset($conditions['policy_types'])) {
            $parts[] = "Policy Types: " . implode(', ', $conditions['policy_types']);
        }

        return implode(' AND ', $parts) ?: 'No conditions';
    }
}
