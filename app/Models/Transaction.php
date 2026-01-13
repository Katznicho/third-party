<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number', 'policy_id', 'client_id', 'pre_authorization_id', 'invoice_id',
        'payment_id', 'type', 'reference_number', 'description', 'amount', 'transaction_status',
        'debit_amount', 'credit_amount', 'balance_before', 'balance_after', 'service_category_id',
        'payment_method', 'transaction_date', 'cleared_date', 'due_date', 'cleared_by', 'notes', 'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date',
        'cleared_date' => 'date',
        'due_date' => 'date',
        'metadata' => 'array',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function preAuthorization(): BelongsTo
    {
        return $this->belongsTo(PreAuthorization::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }
}
