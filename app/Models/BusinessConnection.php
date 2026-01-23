<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_company_id',
        'connected_business_id',
        'connected_business_name',
        'connection_type',
        'notes',
    ];

    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id');
    }

    public function connectedBusiness()
    {
        return $this->belongsTo(InsuranceCompany::class, 'connected_business_id');
    }
}
