<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreAuthorizationTrigger extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_company_id',
        'trigger_type',
        'trigger_name',
        'description',
        'trigger_config',
        'service_category_id',
        'cost_threshold',
        'keywords',
        'auto_create_preauth',
        'require_manual_approval',
        'auto_approval_limit',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
            'keywords' => 'array',
            'cost_threshold' => 'decimal:2',
            'auto_approval_limit' => 'decimal:2',
            'auto_create_preauth' => 'boolean',
            'require_manual_approval' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
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

    public function scopeForTriggerType($query, $triggerType)
    {
        return $query->where('trigger_type', $triggerType);
    }

    /**
     * Check if this trigger matches the given service/item
     */
    public function matches($serviceCategoryId = null, $amount = null, $description = null, $keywords = []): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check service category match
        if ($this->service_category_id && $serviceCategoryId != $this->service_category_id) {
            return false;
        }

        // Check cost threshold
        if ($this->cost_threshold && $amount && $amount < $this->cost_threshold) {
            return false;
        }

        // Check keywords
        if ($this->keywords && !empty($this->keywords)) {
            $descriptionLower = strtolower($description ?? '');
            $keywordsLower = array_map('strtolower', $keywords);
            $allKeywords = array_merge($this->keywords, $keywordsLower);
            
            $matched = false;
            foreach ($this->keywords as $keyword) {
                if (stripos($descriptionLower, strtolower($keyword)) !== false) {
                    $matched = true;
                    break;
                }
            }
            
            if (!$matched) {
                return false;
            }
        }

        // Check trigger_config for additional conditions
        if ($this->trigger_config) {
            // Custom logic based on trigger_config
            // This can be extended based on specific needs
        }

        return true;
    }
}
