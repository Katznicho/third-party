<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceCompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'slug',
        'head_office_address',
        'postal_address',
        'phone',
        'email',
        'website',
        'description',
        'logo_path',
        'is_active',
        'policy_number_format',
        'policy_number_random_length',
        'policy_number_random_type',
        'policy_number_company_code_length',
        'copay_contributes_to_deductible',
        'coinsurance_contributes_to_deductible',
        'required_client_fields',
        'enable_name_dob_verification',
        'enable_id_passport_verification',
        'enable_phone_verification',
        'enable_email_verification',
        'name_mismatch_action',
        'dob_mismatch_action',
        'id_mismatch_action',
        'name_similarity_threshold',
        'dob_tolerance_days',
        'enable_visit_verification',
        'visit_verification_validity_days',
        'email_verification_action',
        'phone_verification_action',
        'id_passport_verification_action',
        'visit_verification_action',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'policy_number_random_length' => 'integer',
            'policy_number_company_code_length' => 'integer',
            'copay_contributes_to_deductible' => 'boolean',
            'coinsurance_contributes_to_deductible' => 'boolean',
            'required_client_fields' => 'array',
            'enable_name_dob_verification' => 'boolean',
            'enable_id_passport_verification' => 'boolean',
            'enable_phone_verification' => 'boolean',
            'enable_email_verification' => 'boolean',
            'name_similarity_threshold' => 'integer',
            'dob_tolerance_days' => 'integer',
            'enable_visit_verification' => 'boolean',
            'visit_verification_validity_days' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }

    public function connectedCompanies()
    {
        return $this->hasMany(BusinessConnection::class, 'insurance_company_id')
            ->with('connectedBusiness');
    }

    public function connectedTo()
    {
        return $this->hasMany(BusinessConnection::class, 'connected_business_id')
            ->with('insuranceCompany');
    }

    /**
     * Get default required fields configuration
     */
    public static function getDefaultRequiredFields(): array
    {
        return [
            'first_name' => true,
            'id_passport_no' => true,
            // All others default to false
            'surname' => false,
            'other_names' => false,
            'title' => false,
            'gender' => false,
            'tin' => false,
            'date_of_birth' => false,
            'marital_status' => false,
            'height' => false,
            'weight' => false,
            'employer_name' => false,
            'occupation' => false,
            'nationality' => false,
            'home_physical_address' => false,
            'office_physical_address' => false,
            'home_telephone' => false,
            'office_telephone' => false,
            'cell_phone' => false,
            'whatsapp_line' => false,
            'email' => false,
            'next_of_kin_surname' => false,
            'next_of_kin_first_name' => false,
            'next_of_kin_other_names' => false,
            'next_of_kin_title' => false,
            'next_of_kin_relation' => false,
            'next_of_kin_id_passport_no' => false,
            'next_of_kin_cell_phone' => false,
            'next_of_kin_email' => false,
            'next_of_kin_post_address' => false,
            'next_of_kin_physical_address' => false,
        ];
    }

    /**
     * Check if a field is required for client creation
     */
    public function isFieldRequired(string $fieldName): bool
    {
        $requiredFields = $this->required_client_fields ?? [];
        
        // If no custom settings, use defaults
        if (empty($requiredFields)) {
            $defaults = self::getDefaultRequiredFields();
            return $defaults[$fieldName] ?? false;
        }
        
        return $requiredFields[$fieldName] ?? false;
    }

    /**
     * Get all required fields
     */
    public function getRequiredFields(): array
    {
        $requiredFields = $this->required_client_fields ?? [];
        
        if (empty($requiredFields)) {
            return array_keys(array_filter(self::getDefaultRequiredFields()));
        }
        
        return array_keys(array_filter($requiredFields));
    }
}
