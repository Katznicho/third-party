<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalQuestion extends Model
{
    protected $fillable = [
        'question_text',
        'question_type',
        'has_exclusion_list',
        'exclusion_keywords',
        'requires_additional_info',
        'additional_info_type',
        'additional_info_label',
        'order',
        'is_active',
    ];

    protected $casts = [
        'has_exclusion_list' => 'boolean',
        'exclusion_keywords' => 'array',
        'requires_additional_info' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(MedicalQuestionResponse::class);
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
