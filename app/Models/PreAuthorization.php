<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreAuthorization extends Model
{
    use HasFactory;

    protected $fillable = [
        'authorization_number',
        'policy_id',
        'client_id',
        'service_category_id',
        'request_description',
        'medical_justification',
        'requested_by',
        'provider_name',
        'provider_address',
        'provider_phone',
        'requested_amount',
        'approved_amount',
        'estimated_amount',
        'status',
        'request_date',
        'required_date',
        'approval_date',
        'expiry_date',
        'service_date',
        'approved_by',
        'approval_notes',
        'rejection_reason',
        'visit_type',
        'visit_number',
        'visit_start_date',
        'visit_end_date',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'estimated_amount' => 'decimal:2',
            'request_date' => 'date',
            'required_date' => 'date',
            'approval_date' => 'date',
            'expiry_date' => 'date',
            'service_date' => 'date',
            'visit_start_date' => 'date',
            'visit_end_date' => 'date',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AuthorizationItem::class, 'pre_authorization_id');
    }

    public function authorizationItems(): HasMany
    {
        return $this->items();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function paymentResponsibilities(): HasMany
    {
        return $this->hasMany(PaymentResponsibility::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
