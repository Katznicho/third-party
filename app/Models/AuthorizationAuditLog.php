<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorizationAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'pre_authorization_id',
        'invoice_id',
        'authorization_rule_id',
        'insurance_company_id',
        'decision',
        'authorization_method',
        'requested_amount',
        'approved_amount',
        'rejected_amount',
        'context_data',
        'rule_evaluation_results',
        'processed_by',
        'notes',
        'rejection_reason',
        'processed_at',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'rejected_amount' => 'decimal:2',
        'context_data' => 'array',
        'rule_evaluation_results' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function preAuthorization(): BelongsTo
    {
        return $this->belongsTo(PreAuthorization::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function authorizationRule(): BelongsTo
    {
        return $this->belongsTo(AuthorizationRule::class);
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scopes
     */
    public function scopeForInsuranceCompany($query, $insuranceCompanyId)
    {
        return $query->where('insurance_company_id', $insuranceCompanyId);
    }

    public function scopeByDecision($query, $decision)
    {
        return $query->where('decision', $decision);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('authorization_method', $method);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('processed_at', '>=', now()->subDays($days));
    }

    /**
     * Helper methods
     */
    public function isAutomatic(): bool
    {
        return $this->authorization_method === 'automatic';
    }

    public function isManual(): bool
    {
        return $this->authorization_method === 'manual';
    }

    public function isApproved(): bool
    {
        return in_array($this->decision, ['auto_approved', 'manually_approved', 'partially_approved']);
    }

    public function isRejected(): bool
    {
        return in_array($this->decision, ['auto_rejected', 'manually_rejected']);
    }
}
