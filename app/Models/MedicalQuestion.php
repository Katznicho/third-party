<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalQuestion extends Model
{
    protected $fillable = [
        'insurance_company_id',
        'question_text',
        'question_type',
        'has_exclusion_list',
        'exclusion_keywords',
        'requires_additional_info',
        'additional_info_type',
        'additional_info_label',
        'order',
        'is_active',
        // Monetary Impact
        'has_monetary_impact',
        'monetary_impact_type',
        'monetary_impact_amount',
        'monetary_impact_is_percentage',
        'monetary_impact_applies_to_response',
        'monetary_impact_description',
    ];

    protected $casts = [
        'has_exclusion_list' => 'boolean',
        'exclusion_keywords' => 'array',
        'requires_additional_info' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
        'has_monetary_impact' => 'boolean',
        'monetary_impact_is_percentage' => 'boolean',
        'monetary_impact_amount' => 'decimal:2',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(MedicalQuestionResponse::class);
    }

    public function insuranceCompany(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    /**
     * Check if a response triggers exclusion
     */
    public function triggersExclusion(string $response): bool
    {
        if (!$this->has_exclusion_list) {
            return false;
        }

        if ($response === 'yes' && !empty($this->exclusion_keywords)) {
            return true;
        }

        // Check if response contains any exclusion keywords
        if (!empty($this->exclusion_keywords)) {
            $responseLower = strtolower($response);
            foreach ($this->exclusion_keywords as $keyword) {
                if (stripos($responseLower, strtolower($keyword)) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}
