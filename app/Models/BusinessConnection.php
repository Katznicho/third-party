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
        'status',
        'block_reason',
        'blocked_at',
        'blocked_by',
        'notes',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
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
     * Get the user who blocked this connection.
     */
    public function blockedByUser()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /**
     * Check if this connection is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    /**
     * Check if this connection is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if this connection is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Block this connection.
     */
    public function block(string $reason, ?int $blockedBy = null, string $status = 'blocked'): self
    {
        $this->update([
            'status' => $status,
            'block_reason' => $reason,
            'blocked_at' => now(),
            'blocked_by' => $blockedBy ?? auth()->id(),
        ]);

        return $this;
    }

    /**
     * Suspend this connection.
     */
    public function suspend(string $reason, ?int $suspenedBy = null): self
    {
        return $this->block($reason, $suspenedBy, 'suspended');
    }

    /**
     * Reactivate this connection.
     */
    public function reactivate(): self
    {
        $this->update([
            'status' => 'active',
            'block_reason' => null,
            'blocked_at' => null,
            'blocked_by' => null,
        ]);

        return $this;
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
