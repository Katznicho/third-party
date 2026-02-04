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
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'policy_number_random_length' => 'integer',
            'policy_number_company_code_length' => 'integer',
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
}
