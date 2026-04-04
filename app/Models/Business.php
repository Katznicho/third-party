<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'address',
        'website',
        'description',
    ];

    /**
     * Get the settings for this business.
     */
    public function settings()
    {
        return $this->hasMany(BusinessSetting::class);
    }

    /**
     * Get visit authorization duration setting.
     */
    public function getVisitAuthorizationDurationAttribute()
    {
        return BusinessSetting::getVisitAuthorizationDuration($this->id);
    }
}
