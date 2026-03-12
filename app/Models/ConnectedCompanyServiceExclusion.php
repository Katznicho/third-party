<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConnectedCompanyServiceExclusion extends Model
{
    protected $fillable = [
        'insurance_company_id',
        'business_connection_id',
        'service_category',
        'service_code',
        'reason',
        'is_active',
    ];

    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function businessConnection()
    {
        return $this->belongsTo(BusinessConnection::class, 'business_connection_id');
    }
}

