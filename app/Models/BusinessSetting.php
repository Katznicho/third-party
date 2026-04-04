<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_connection_id',
        'setting_key',
        'setting_value',
    ];

    protected $casts = [
        'setting_value' => 'json',
    ];

    /**
     * Get business connection that owns setting.
     */
    public function businessConnection()
    {
        return $this->belongsTo(BusinessConnection::class);
    }

    /**
     * Get a setting value for a business connection.
     */
    public static function getValue($businessConnectionId, $key, $default = null)
    {
        $setting = static::where('business_connection_id', $businessConnectionId)
            ->where('setting_key', $key)
            ->first();

        return $setting ? $setting->setting_value : $default;
    }

    /**
     * Set a setting value for a business connection.
     */
    public static function setValue($businessConnectionId, $key, $value)
    {
        return static::updateOrCreate(
            [
                'business_connection_id' => $businessConnectionId,
                'setting_key' => $key,
            ],
            [
                'setting_value' => $value,
            ]
        );
    }

    /**
     * Get visit authorization duration for a business connection.
     */
    public static function getVisitAuthorizationDuration($businessConnectionId)
    {
        $duration = static::getValue($businessConnectionId, 'visit_authorization_duration', 7);
        return (int) $duration;
    }
}
