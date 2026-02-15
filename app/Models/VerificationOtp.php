<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class VerificationOtp extends Model
{
    protected $fillable = [
        'client_id',
        'verification_type',
        'identifier',
        'otp',
        'attempts',
        'expires_at',
        'verified_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Get the client that owns this verification OTP
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Check if OTP is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if OTP is verified
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Check if OTP has exceeded max attempts
     */
    public function hasExceededMaxAttempts(int $maxAttempts = 5): bool
    {
        return $this->attempts >= $maxAttempts;
    }

    /**
     * Mark OTP as verified
     */
    public function markAsVerified(): void
    {
        $this->update([
            'verified_at' => now(),
        ]);
    }

    /**
     * Increment verification attempts
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Scope to get valid (not expired, not verified) OTPs
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
            ->whereNull('verified_at');
    }

    /**
     * Scope to get expired OTPs
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Clean up expired OTPs (can be called via scheduled task)
     */
    public static function cleanupExpired(int $daysOld = 7): int
    {
        return self::where('expires_at', '<', now()->subDays($daysOld))
            ->delete();
    }
}
