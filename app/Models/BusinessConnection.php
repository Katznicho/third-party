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

    /**
     * Get settings for this business connection.
     */
    public function settings()
    {
        return $this->hasMany(BusinessSetting::class, 'business_connection_id');
    }

    /**
     * Get visit authorization duration setting.
     */
    public function getVisitAuthorizationDurationAttribute()
    {
        return BusinessSetting::getVisitAuthorizationDuration($this->id);
    }
}
