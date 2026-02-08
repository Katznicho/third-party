<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitIdentityVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_company_id',
        'visit_id',
        'policy_id',
        'client_id',
        'provided_name',
        'provided_date_of_birth',
        'provided_id_passport_no',
        'provided_phone',
        'provided_email',
        'matched_name',
        'matched_date_of_birth',
        'matched_id_passport_no',
        'matched_phone',
        'matched_email',
        'verification_status',
        'verification_method',
        'name_similarity_score',
        'name_match',
        'dob_match',
        'id_match',
        'phone_match',
        'email_match',
        'mismatch_reasons',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
        'verified_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'provided_date_of_birth' => 'date',
            'matched_date_of_birth' => 'date',
            'name_match' => 'boolean',
            'dob_match' => 'boolean',
            'id_match' => 'boolean',
            'phone_match' => 'boolean',
            'email_match' => 'boolean',
            'reviewed_at' => 'datetime',
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function isRejected(): bool
    {
        return $this->verification_status === 'rejected';
    }

    public function isFlagged(): bool
    {
        return $this->verification_status === 'flagged';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
